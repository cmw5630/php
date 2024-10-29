<?php

namespace App\Console\Commands\DataControll;

use App\Console\Commands\DataControll\PlayerStatusEventController\PlayerStatusChangeEventController;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\ComebackHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\DiedHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\RetiredHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\RevivedHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\UnknownHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\DeactivatedHandler;
use App\Console\Commands\DataControll\PlayerStatusEventHandlers\OptaMistakeHandler;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Squad\PlayerChangeStatus;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\YesNo;
use App\Enums\ParserMode;
use App\Enums\PlateCardFailLogType;
use App\Enums\System\NotifyLevel;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Squad;
use App\Models\game\PlateCard;
use App\Models\log\StatusActiveChangedPlayer;
use App\Models\log\PlateCardFailLog;
use App\Models\meta\RefCountryCode;
use Artisan;
use DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class PlateCardUpdator extends PlateCardBase
{
  use FantasyMetaTrait;

  protected $feedNick;
  private $nationalityCodeSet;


  public function __construct()
  {
    parent::__construct();
    $this->feedNick = 'PCU';
    // squads테이블에서 nonPlayer, 챔피언스리그를 softdelte하고 시작
    logger(Squad::Where('type', '!=', PlayerType::PLAYER)
      ->orWhere('league_id', '=', config('constant.LEAGUE_CODE.UCL')) // 챔피언스리그 삭제에 포함
      ->delete() . '명의 챔피언스리그 또는 Non player 삭제(softDelete)');

    $this->nationalityCodeSet = RefCountryCode::selectRaw('alpha_3_code, nationality_id')
      ->whereNotNull('nationality_id')->get()->keyBy('nationality_id')->toArray();
  }


  public function guessChangedType(&$_squadRow)
  {
    // trigger를 타지 않은 데이터에 대해(이 때는 status='active', active='yes'로 변경된 정보(COMBACK, REVIVED)는 트리거를 타거나, squads -> plate_cards로 직접 업데이트되므로 조건에서 고려하지 않음.)
    // squads 데이터 중 !!!활동중이지 않은 상태!!!의 정보로 그 선수의 변경 상태를 추론한다.
    $_squadRow['old_status'] = PlayerStatus::ACTIVE;
    $_squadRow['old_active'] = YesNo::YES;

    if ($_squadRow['status'] === PlayerStatus::RETIRED) {
      $_squadRow['changed_type'] = PlayerChangeStatus::RETIRED;
    } else if ($_squadRow['status'] === PlayerStatus::DIED) {
      $_squadRow['changed_type'] = PlayerChangeStatus::DIED;
    } else if ($_squadRow['status'] === PlayerStatus::ACTIVE && $_squadRow['active'] === YesNo::NO) {
      $_squadRow['changed_type'] = PlayerChangeStatus::DEACTIVATED;
    } else {
      $_squadRow['changed_type'] = PlayerChangeStatus::UNKNOWN;
      $_squadRow['old_status'] = null;
      $_squadRow['old_active'] = null;
    }
  }


  public function start(): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            # code...
            break;
          case FantasySyncGroupType::DAILY:
            break;
          case FantasySyncGroupType::CONDITIONALLY:
            break;
          default:
            # code...
            break;
        }

        // case ParserMode::PARAM:
        //   if ($this->getParam('mode') === 'all') {
        //     $ids = $this->getAllIds();
        //   }
        //   # code...
        //   break;
        // default:
        //   # code...
        //   break;
    }

    /**
     * squads 에서 status='active', active='yes' 인 데이터 plate_cards에 넣기
     * 넣은 데이터는 squads에서 deleted_at 처리
     * status_active_...테이블 참조하여 적절하게 처리하는 로직 구현 
     * 역시 status_active..테이블에서 처리한 데이터 deleted_at 처리
     */

    DB::beginTransaction();
    try {
      Schema::connection('log')->disableForeignKeyConstraints();
      // squads 테이블에서 새로운 선수 정보 read
      $newPlayerSquadsQuery = Squad::exceptLeague(config('constant.LEAGUE_CODE.UCL'))
        ->currentSeason()
        ->activePlayers()
        ->availablePositions()
        ->infosForSearch();
      // ->with(['league:id,league_code']);

      logger($newPlayerSquadsQuery->count() . '개의 활성화된 새로운 선수 정보가 감지되었습니다.');

      $this->makePlateCards($newPlayerSquadsQuery->clone());

      // 최초수집 또는 재수집 시 반영되지 않은 선수 변환상태 체크 및 plate_cards 테이블 보정
      $this->insertedPlayerStatusLogsChecker();

      // 현재 활성화된 시즌 내의 실제로 발생된 선수 변화 상태 처리
      $this->handlePlayerStatusChangeEvent();

      // plate_cards 검증
      $this->verifyPlateCard();

      $newPlayerSquadsQuery->delete();

      if ($this->getSyncGroup() !== FantasySyncGroupType::CONDITIONALLY) {
        (new PlayerCurrentMetaRefUpdator(null))->update(); // 위치, 순서 바꾸지 말 것!
      }
      DB::commit();
      logger('plate_cards 업데이트 정상 완료');
    } catch (\Exception $e) {
      logger($e);
      logger('plate_cards 업데이트 rollback');
      DB::rollBack();
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }

    DB::beginTransaction();
    try {
      Schema::connection('log')->disableForeignKeyConstraints();
      (new PlateCardPrice())->update();
      // plate_card 가격 초기화
      logger('plate_card 가격 초기화 완료');
      DB::commit();
    } catch (\Exception $e) {
      logger($e);
      logger('plate_cards 가격 초기화 rollback');
      DB::rollBack();
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    logger('start PCEU with PCU destruct');
    Artisan::call('plate-card-etc-update');
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }

  public function makePlateCards(Builder $_squadQuery)
  {
    // $commonColnams = $this->getCommonColumns(Squad::class, PlateCard::class);

    $_squadQuery->chunk(250, function ($players) {
      logger('~250');
      foreach ($players as $squadrow) {
        $squadrow->nationality_code = $this->nationalityCodeSet[$squadrow->nationality_id]['alpha_3_code'] ?? null;
        $this->upsertOnePlateCard($squadrow->toArray());
      }
    });
  }


  public function insertedPlayerStatusLogsChecker()
  {
    // squads 테이블에서 플레이어의 상태변화는 기본적으로 update를 통해 이루어저 trigger를 타는 것이 기본임.
    // squads 테이블의 최초 수집 또는  데이터 손실된 후 데이터를 새롭게 수집할 때에는 squads에 상태변화 데이터가 insert되어 트리거를 타지 않음에 대한 처리하는 method
    // (insert로 새롭게 들어오는 데이터는 update 트리거를 타지 않으므로) 데이터 손실시점과 수집시점 사이에 손실된 데이터의 선수상태가 (deactive로)변한 경우에 대한 처리가 필요.
    // plate_cards 생성 후처리가 있으므로 plate_cards 생성 후에 호출하도록 한다.

    $nonActivePlayerLogs = Squad::where('type', '=', PlayerType::PLAYER)
      ->where(function (Builder $query) {
        $query
          ->orWhere('status', '!=', PlayerStatus::ACTIVE)
          ->orWhere('active', '!=', YesNo::YES);
      })
      ->where('league_id', '!=', config('constant.LEAGUE_CODE.UCL')); // 챔피언스리그 제외
    logger('squads 테이블에 insert로 들어온' . $nonActivePlayerLogs->clone()->count() . '개의 새로운 비활동 선수정보 로그 감지');

    $statusLogColumns = (new StatusActiveChangedPlayer)->getTableColumns(true);
    $newLogsCount = 0;
    // $commonColnams = $this->getCommonColumns(Squad::class, PlateCard::class);

    $nonActivePlayerLogs->clone()->chunk(30, function ($nonActiveLogsFromSquads) use ($statusLogColumns, &$newLogsCount) {
      foreach ($nonActiveLogsFromSquads as $nonActiveLogSquadsRow) {
        // 동일한 비활성 로그가 존재하지 않을 때만(== 트리거를 타지 않았을 때만) 새로운 로그 기록
        if (!StatusActiveChangedPlayer::getSameLogForSquad($nonActiveLogSquadsRow->toArray())->exists()) {
          $newLogsCount++;
          $newStatusLogRow = (new StatusActiveChangedPlayer);
          $newStatusLogRow['changed_type'] = $this->guessChangedType($nonActiveLogSquadsRow);
          $newStatusLogRow['squads_id'] = $nonActiveLogSquadsRow['id'];
          foreach ($nonActiveLogSquadsRow->toArray() as $squadColName => $squadValue) {
            if ($squadColName !== 'id' && in_array($squadColName, $statusLogColumns)) {
              $newStatusLogRow->{$squadColName} = $squadValue;
            }
          }
          $newStatusLogRow->save();
          // dd($newStatusLogRow->toArray());
          // // StatusActiveChangedPlayers::create()
          // logger($squadRow->toArray());
          // dd("x");
        } else {
          logger('이미 동일할 선수 상태(비활성)변환 로그가 있는 경우 감지!');
          // 데이터 손실시점과 수집시점 사이에 새롭게 생긴 상태변화 정보가 기존에 저장된 로그기록과 동일한 경우(희박한 확률)
          // 해당 플레이어의 현재 상태변화정보를 >>>>>!!놓치게 된다!!<<<<<<
          // 1. 동일한 리그 내에서 이적 -> plate_cards 생성 로직에서 처리됨.
          // 2. 이적하지 않은 경우 -> 그대로 두면 된다.
          // 3. 다른 리그로 이적 -> 해당 플레이어를 plate_cards에서 제거해야함.
          // 위 3번 상태에 대한 처리를 놓치게 된다. 
          // 단순히 어떤 경우인지 파악할 수 없으므로 이럴 경우엔 해당 플레이어에 대해 squads 테이블에 status='active', active='yes'인 레코드가 (소프트deleted된 내용 포함)
          // 존재한다면 해당 레코드와 동일한 값으로 직접 plate_cards를 업데이트 시켜주도록 한다. 
          // 존재하지 않으면 해당 플레이어 plate_cards에서 삭제.
          // 보류

          $activePlayerSquadsQuery = Squad::withTrashed()
            ->exceptLeague(config('constant.LEAGUE_CODE.UCL'))
            ->currentSeason()
            ->activePlayers()
            ->availablePositions()
            ->where('player_id', '=', $nonActiveLogSquadsRow['player_id'])
            ->infosForSearch();
          // ->with(['league:id,league_code']);

          if ($activePlayerSquadsQuery->exists()) {
            //* ->임시검사코드
            if ($activePlayerSquadsQuery->count() !== 1) {
              logger('테이블 무결성 오류 활성화된 플레이어 정보는 반드시 1개여야 한다. 챔피언스 리그 제외했는지 확인할 것!');
              logger($activePlayerSquadsQuery->get()->toArray());
              // 텔레그램 통보
            }
            //* <-임시검사코드
            $playerRow = $activePlayerSquadsQuery->get()[0];
            // plate_card upsert
            $this->upsertOnePlateCard($playerRow->toarray());
            logger('c');
          } else {
            // 삭제
            PlateCard::where('player_id', '=', $nonActiveLogSquadsRow['player_id'])->delete() . '<- deleted count';
          }
        }
      }
    });
    logger('---------------->');
    $nonActivePlayerLogs->delete();
    logger('<----------------');
    logger('트리거를 타지않은 선수정보 상태변화 로그 ' . $newLogsCount . '개 기록');
  }


  public function handlePlayerStatusChangeEvent()
  {
    /**
     * deactivate된 선수에 대한 plate_cards 테이블 내 처리는
     * 현재 활성화된 시즌에 대해서만 처리하고
     * 나머지는 모두 deleted_at 처리하면 된다.
     * 처리 기준(해당 시즌에 해당 선수의 plate_card가 만들어졌는지 여부)
     * 1. 선수 plate_card가 만들어지기 전 deactive된 정보는 그냥 deleted_at
     * 2. 선수 plate_card가 만들어진 이후에 deactive된 처리는 handle되어야 함.
     */
    $statusLogs = StatusActiveChangedPlayer::withoutTrashed();
    $cloneStatusLogs = $statusLogs->clone();

    // 옵저버(핸들러) 등록
    $statusEventController = new PlayerStatusChangeEventController();
    $statusEventController = $this->attachPlayerStatusHandlers($statusEventController);
    // <--------
    $statusLogs->withinCurrentSeason()->chunk(10, function ($_playerStatusChangeLogs) use ($statusEventController) {
      foreach ($_playerStatusChangeLogs as $idx => $changeLog) {
        // 이벤트 핸들
        $statusEventController->updateChangedStatus($changeLog->toArray());
      }
    });
    $cloneStatusLogs->delete();
  }


  public function attachPlayerStatusHandlers(PlayerStatusChangeEventController $_statusController): PlayerStatusChangeEventController
  {
    $listeners = [
      new DeactivatedHandler(),
      new DiedHandler(),
      new ComebackHandler(),
      new RetiredHandler(),
      new RevivedHandler(),
      new OptaMistakeHandler(),
      new UnknownHandler(),
    ];

    // 옵저버(리스너) 등록
    foreach ($listeners as $lsn) {
      $_statusController->registerListener($lsn);
    }
    return $_statusController;
  }

  protected function makePlateCardFailLog($_plateCardId, $_playerId, $_failType)
  {
    $data = [];
    $data['plate_card_id'] = $_plateCardId;
    $data['player_id'] = $_playerId;
    $data['fail_type'] = $_failType;
    PlateCardFailLog::updateOrCreateEx(
      [
        'player_id' => $_playerId,
        'fail_type' => $_failType
      ],
      $data
    );
  }

  protected function checkOverSquad(Builder $_squadQuery, Builder $_cardQuery)
  {
    $overSquadPlayers = $_squadQuery->clone()
      ->select(DB::raw('player_id'))
      ->whereNotIn('player_id', $_cardQuery->clone()->pluck('player_id')->toArray())->pluck('player_id')->toArray();

    if (count($overSquadPlayers)) {
      logger('1. plate_card가 만들어지지 않은(또는 delete_at 상태의) 선수 id 리스트');
      logger($overSquadPlayers);
      logger('해결방법1: squads 테이블에서 만들어져야할 player의 deleted_at 을 null로 세팅한다.(주의:status, active값을 임의로 고치면 안됨)');
      logger('플레이트 카드를 만들 정보의 status, active는 각각 active, yes여야 하면 그렇지 않을 경우 위 방법으로 plate_card를 생성할 수 없음.');

      foreach ($overSquadPlayers as $playerId) {
        $this->makePlateCardFailLog(null, $playerId, PlateCardFailLogType::OVERSQUAD);
      }
    }
  }


  protected function checkOverCard(Builder $_squadQuery, Builder $_cardQuery)
  {
    $overCardPlayers = $_cardQuery->clone()
      ->select(DB::raw('player_id'))
      ->whereNotIn('player_id', $_squadQuery->clone()->pluck('player_id')->toArray())->pluck('player_id')->toArray();
    if (count($overCardPlayers)) {
      logger('2. 판매되지 말아야할 player_id');
      logger($overCardPlayers);
      logger('해결방법1: plate_card 테이블에서 위 플레이어들을 deleted_at 시킨다.');
      logger('<< 지워져야 할 plate_card id');

      foreach ($overCardPlayers as $playerId) {
        $plateCardQuery = PlateCard::where('player_id', $playerId);
        $plateCardId = $plateCardQuery->clone()->first('id')->toArray()['id'];
        $plateCardQuery->delete();
        $this->makePlateCardFailLog($plateCardId, $playerId, PlateCardFailLogType::OVERCARD);
      }
    }
  }


  protected function checkOverActive(Builder $_squadQuery)
  {
    $sub = $_squadQuery->clone()
      ->select(DB::raw('COUNT(player_id) as player_count, player_id'))
      ->groupBy('player_id')
      ->orderBy(DB::raw('COUNT(player_id)'), 'desc');

    $squadActiveOverlapList =
      json_decode(json_encode(DB::query()->fromSub($sub, 'sub')
        ->where('sub.player_count', '>', 1)
        ->get()
        ->toArray()), true);
    if (count($squadActiveOverlapList)) {
      foreach ($squadActiveOverlapList  as $squadData) {
        $playerId = $squadData['player_id'];
        $plateCardQuery = PlateCard::where('player_id', $playerId);
        $plateCardId = $plateCardQuery->clone()->first('id')->toArray()['id'];
        // $plateCardQuery->delete();

        $this->makePlateCardFailLog($plateCardId, $playerId, PlateCardFailLogType::OVERACTIVE);
      }
      // 에러 처리하지 않고 테이블에 기록하여 운영으로 돌리기
      logger('3. squads 정보에 현재시즌에 활동중인(active=yes) 선수 정보가 중복된 경우가 존재 >>');
      logger($squadActiveOverlapList);
      logger('체크사항: 중복된 플레이어의 정보중 어떤 정보가 플레이트카드로 만들어졌는지 체크하고 필요하면 수정할 것!');
    }
  }

  protected function verifyPlateCard(): void
  {
    $cardQuery = $this->setCommonWhere(PlateCard::getModel());
    $squadQuery = $this->setCommonWhere(Squad::getModel())->withTrashed();
    logger('active된 plate card 개수 = ' . $cardQuery->clone()->count());
    logger('squad active player 개수 = ' . count($squadQuery->clone()->get('player_id')->unique('player_id')->toArray()));
    __telegramNotify(
      NotifyLevel::CRITICAL,
      'plate card update',
      sprintf('active된 plate card 개수 = %s, squad active player 개수 = %s', $cardQuery->clone()->count(), count($squadQuery->clone()->get('player_id')->unique('player_id')->toArray()))
    );

    $this->checkOverSquad($squadQuery, $cardQuery);

    $this->checkOverCard($squadQuery, $cardQuery);

    $this->checkOverActive($squadQuery);
  }
}

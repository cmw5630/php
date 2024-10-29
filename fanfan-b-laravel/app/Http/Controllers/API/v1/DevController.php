<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\PlateCardActionType;
use App\Enums\PointRefType;
use App\Enums\PointType;
use App\Enums\PurchaseOrderType;
use App\Http\Controllers\Controller;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\FantasyCalculator;
use App\Models\user\User;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\data\Schedule;
use App\Models\game\DraftSelection;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\GameSchedule;
use App\Models\game\PlateCard;
use App\Models\log\DraftLog;
use App\Models\log\DraftSelectionLog;
use App\Models\log\PlateCardDailyAction;
use App\Models\order\DraftOrder;
use App\Models\user\UserPlateCard;
use App\Services\Order\OrderService;
use App\Services\User\UserPointService;
use Carbon\Carbon;
use DB;
use ReturnData;
use Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Schema;
use Throwable;

class DevController extends Controller
{
  use LogTrait, GameTrait;
  protected OrderService $orderService;
  protected UserPointService $userPointService;
  protected ?Authenticatable $user;

  public function __construct(OrderService $_orderService, UserPointService $_userPointService, ?Authenticatable $_user)
  {
    $this->orderService = $_orderService;
    $this->userPointService = $_userPointService;
    $this->user = $_user;
  }

  // no 사용 (config 정리햇음.)
  public function randomLineup($_gameId)
  {
    $lineupArray = config('constant.GAME_SLOT_INDEX');

    $game = Game::find($_gameId);
    try {
      if (now() > $game->start_date) {
        throw new Exception('이미 시작된 게임');
      }
      $teams = [];
      GameSchedule::where('game_id', $_gameId)
        ->with(['schedule.home', 'schedule.away'])
        ->get()
        ->map(function ($item) use (&$teams) {
          $teams[] = $item->schedule->home_team_id;
          $teams[] = $item->schedule->away_team_id;
        });

      $slots = config('constant.GAME_SLOTS');
      array_map(function ($val) use (&$lineup) {
        $lineup[$val] = null;
      }, $slots);
      $lineupData = [];

      PlateCard::query()
        ->whereIn('team_id', $teams)
        ->get()
        ->shuffle()
        ->map(function ($item) use (&$lineupArray, &$lineup, &$lineupData) {
          foreach ($lineup as $slotPosition => $slotCard) {
            if (is_null($slotCard)) {
              $selectedPosition = preg_replace('/[0-9]/', '', $slotPosition);

              if ($selectedPosition === $item->position) {
                [, $userPlateCard] = $this->orderService->saveOrder([
                  'type' => PurchaseOrderType::DIRECT,
                  'quantity' => 1,
                  'total_price' => $item->price,
                  'point_type' => PointType::CASH,
                  'plate_card_id' => $item->id
                ]);
                PlateCardDailyAction::updateOrCreate([
                  'type' => PlateCardActionType::SALE,
                  'player_id' => $item['player_id'],
                  'position' => $item['position'],
                  'based_at' => now()->toDateString()
                ], ['count' => DB::raw('count+1')]);

                // point 차감
                $this->userPointService->minusUserPointWithLog(
                  $item->price,
                  PointType::CASH,
                  PointRefType::ORDER
                );

                $placeIndex = $lineupArray[$selectedPosition][0];
                unset($lineupArray[$selectedPosition][0]);
                $lineupArray[$selectedPosition] = array_values($lineupArray[$selectedPosition]);

                $lineup[$slotPosition] = ['id' => $item->id, 'position' => $slotPosition, 'price' => $item->price];
                $lineupData[] = ['id' => $userPlateCard->id, 'position' => $selectedPosition, 'place_index' => $placeIndex];
                break;
              }
            }
          }
        });

      return json_encode($lineupData);

      $sub = PlateCard::query()
        ->selectRaw('*, row_number() over(PARTITION BY position order by rand()) as rnum')
        ->whereIn('team_id', $teams);
      $cards = DB::query()->select(['id', 'player_id', 'team_id', 'position', 'price'])->fromSub($sub, 'sub')->where('sub.rnum', '<', 4)->get();


      $cards = UserPlateCard::whereHas('plateCard', function ($query) use ($teams) {
        $query->whereIn('team_id', $teams);
      })
        ->get();
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 이번주 월요일~일요일까지의 7일치 경기로 리그별 game 생성.
  // 사용X
  public function makeGame()
  {
    try {
      $dateStringSet = $this->getDateStringSet();
      $startDate = $dateStringSet['this_week']['start'];
      $endDate = $dateStringSet['this_week']['end'];
      // dd($endDate);

      // gameSchedule에 묶인 마지막 schedule Round

      $schedules = Schedule::where([
        ['status', ScheduleStatus::FIXTURE],
        ['league_id', '!=', config('constant.LEAGUE_CODE.UCL')]
      ])
        ->doesntHave('gameSchedule')
        ->whereBetween('started_at', [$startDate, $endDate])
        ->oldest()
        ->get()
        ->mapToGroups(function ($info) {
          return [$info['season_id'] => $info];
        })
        ->toArray();

      $i = 0;
      $j = 0;
      foreach ($schedules as $seasonId => $schedule) {
        $game_id = 0;
        $maxRound = Game::where('season_id', $seasonId)->selectRaw('IFNULL(MAX(game_round_no)+1,1) AS maxRound')->value('maxRound');

        // dd($maxRound);
        foreach ($schedule as $info) {
          $isInGameSchedules = GameSchedule::where('schedule_id', $info['id'])
            ->where('status', ScheduleStatus::FIXTURE);
          if ($isInGameSchedules->exists()) {
            // logger($maxRound);
            // 해당 경기는 이미 game_id 가 할당되었음.
            logger("Already Exist Match >> " . $info['id']);
          } else {
            // logger($maxRound);
            if ($game_id == 0) {
              // game 생성
              $game = new Game();
              $game->season_id = $info['season_id'];
              $game->game_round_no = $maxRound;
              $game->rewards = 0;
              $game->prize_rate = 0;
              $game->start_date = $info['started_at'];
              $game->end_date = $info['started_at'];
              $game->is_popular = 1;
              $game->save();
              $game_id = $game->id;
              $j++;
            }
            $currentGame = Game::where('id', $game_id);
            $gameInfo = $currentGame->first();

            // 기존 end_date 와 현재 schedule 의 start_date 를 비교해서 더 나중경기로 end_date update
            if ($gameInfo['end_date'] < $info['started_at']) {
              $currentGame->update(['end_date' => $info['started_at']]);
            }

            $gameSchedule = new GameSchedule();
            $gameSchedule->schedule_id = $info['id'];
            $gameSchedule->game_id = $gameInfo['id'];
            $gameSchedule->status = $info['status'];
            $gameSchedule->save();
            $i++;
          }
        }
      }
      $result = $startDate . ' 부터 ' . $endDate . ' 까지 ' . $i . ' 개의 스케쥴로 ' . $j . ' 개의 리그별 게임생성';
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 선택한 경기에 강화 3개 random 걸기
  public function randomDraft($_schedule_id)
  {
    $selections = [
      PlayerPosition::ATTACKER => [
        [
          'assists' => 1,
          'goals' => 3,
          'accurate_crosses' => 1,
          'offside_provoked' => 1,
          'ground_duels_won' => 1
        ],
        [
          'winning_goal' => 1,
          'goals' => 2,
          'key_passes' => 1,
          'blocks' => 1,
          'recoveries' => 2
        ],
        [
          'shots_on_target' => 2,
          'goals' => 1,
          'pass_accuracy' => 1,
          'clean_sheet' => 1,
          'duels_won' => 3
        ]
      ],
      PlayerPosition::DEFENDER => [
        [
          'successful_dribbles' => 2,
          'goals' => 1,
          'passes_into_final_third' => 1,
          'tackles_won' => 2,
          'ground_duels_won' => 1
        ],
        [
          'winning_goal' => 1,
          'goals' => 2,
          'key_passes' => 2,
          'clearances' => 1,
          'interceptions' => 1
        ],
        [
          'assists' => 1,
          'goals' => 3,
          'pass_accuracy' => 1,
          'clean_sheet' => 1,
          'recoveries' => 2
        ]
      ],
      PlayerPosition::MIDFIELDER => [
        [
          'assists' => 1,
          'goals' => 3,
          'accurate_crosses' => 1,
          'tackles_won' => 2,
          'clearances' => 1
        ],
        [
          'shots_on_target' => 2,
          'goals' => 2,
          'key_passes' => 1,
          'clean_sheet' => 1,
          'aerial_duel_won' => 1
        ],
        [
          'successful_dribbles' => 2,
          'goals' => 1,
          'accurate_long_passes' => 2,
          'offside_provoked' => 1,
          'recoveries' => 2
        ]
      ],
      PlayerPosition::GOALKEEPER => [
        [
          'accurate_crosses' => 1,
          'offside_provoked' => 1,
          'ground_duels_won' => 1,
          'saved_in_box' => 1,
          'saves' => 2
        ],
        [
          'passes_into_final_third' => 1,
          'clearances' => 3,
          'interceptions' => 1,
          'punches' => 1,
          'saves' => 1
        ],
        [
          'accurate_long_passes' => 2,
          'blocks' => 1,
          'duels_won' => 1,
          'acted_as_sweeper' => 1,
          'saves' => 3
        ]
      ],
    ];

    /*
    * 1. 받은 schedule_id 로 schedule 찾기
    * 2. 해당 shcedule 의 home, away team 들로 plate_cards 찾기
    * 3. 카드 구매
    * 4. Draft 저장
    */

    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    // 1. 받은 schedule_id 로 schedule 찾기 [league globalScope]
    $targetSchedule = Schedule::has('league')->find($_schedule_id)?->toArray();

    // 30분 이내가 아니고, fixture 인거
    if (
      is_null($targetSchedule) ||
      ($targetSchedule['status'] !== ScheduleStatus::FIXTURE ||
        Carbon::parse(now())->floatDiffInMinutes($targetSchedule['started_at'], false) < 30)
    ) {
      return ReturnData::setError('강화 시도 가능한 경기 상태가 아니거나 강화 가능 시간(경기 시작 시간 30분 이전)이 지났습니다.')->send(Response::HTTP_BAD_REQUEST);
    }

    // 2. 해당 shcedule 의 home, away team 들로 plate_cards 3개 찾기
    $draftAvailableCards = PlateCard::whereIn('team_id', [
      $targetSchedule['home_team_id'],
      $targetSchedule['away_team_id']
    ])->orderByRaw('RAND()')->limit(5)->get()->toArray();

    DB::beginTransaction();
    try {
      $ids = '';
      foreach ($draftAvailableCards as $draftCard) {
        $priceGrade = $draftCard['grade'];
        $policy = $draftCalculator->getDraftPolicy($priceGrade);

        $ids .= $draftCard['id'] . ',';
        // 3. 카드 구매
        $this->orderService->saveOrder([
          'type' => PurchaseOrderType::DIRECT,
          'quantity' => 1,
          'total_price' => $draftCard['price'],
          'point_type' => PointType::GOLD,
          'plate_card_id' => $draftCard['id']
        ]);

        PlateCardDailyAction::updateOrCreate([
          'type' => PlateCardActionType::SALE,
          'player_id' => $draftCard['player_id'],
          'position' => $draftCard['position'],
          'based_at' => now()->toDateString()
        ], ['count' => DB::raw('count+1')]);

        // point 차감
        $this->minusUserPointWithLog(
          $draftCard['price'],
          PointType::GOLD,
          PointRefType::ORDER
        );

        $userPlateCard = UserPlateCard::where([
          ['user_id', $this->user->id],
          ['plate_card_id', $draftCard['id']],
          ['status', PlateCardStatus::PLATE]
        ])->first();

        // 4. Draft 저장
        $rand = rand(0, 2);
        $ranDomselections = $selections[$draftCard['position']][$rand];

        $totalCost = 0;
        $totalLevel = 0;
        $totalPrice = 0;

        foreach ($draftCalculator->getDraftCategoryMetaTable() as $cate => $values) {
          foreach ($values as $name => $meta) {
            if (in_array($name, array_keys($ranDomselections))) {
              $totalCost += $meta['cost'];
              $totalLevel += $ranDomselections[$name];
              // if ($meta['levelMap']['price'])
              if (empty($meta['levelMap']['value'][$ranDomselections[$name]])) {
                $result['isValid'] = false;
                $result['message'] = sprintf('%s 스탯 레벨(%s) 초과 오류', $name, $ranDomselections[$name]);

                return $result;
              }
            }
          }
        }

        foreach ($policy['price']['table'] as $idx => $ref) {
          if ($ref['level'] === $totalLevel) {
            $totalPrice = $ref['price'];
            break;
          }
        }
        $type = PointType::GOLD;

        // 1. 결제 user point 차감 draft
        $this->minusUserPointWithLog(
          $totalPrice,
          // $policy['price']['type'],
          $type,
          PointRefType::UPGRADE,
          'draft',
        );

        // 2. draft_orders 기록
        DraftOrder::create([
          'user_id' => $this->user->id,
          'user_plate_card_id' => $userPlateCard['id'],
          'upgrade_point' => $totalPrice,
          // 'upgrade_point_type' => $policy['price']['type'],
          'upgrade_point_type' => $type,
        ]);

        // 3. draft_selections 저장
        DraftSelection::create(
          array_merge([
            'user_id' => $this->user->id,
            'user_plate_card_id' => $userPlateCard['id'],
            'schedule_id' => $targetSchedule['id'],
            'schedule_started_at' => $targetSchedule['started_at'],
            'player_id' => $draftCard['player_id'],
            'summary_position' => $draftCard['position'],
          ], $ranDomselections)
        );

        // +) draft_selection_logs 기록
        DraftSelectionLog::create(
          array_merge([
            'user_id' => $this->user->id,
            'user_plate_card_id' => $userPlateCard['id'],
            'league_id' => $draftCard['league_id'],
            'team_id' => $draftCard['team_id'],
            'schedule_id' => $targetSchedule['id'],
            'schedule_started_at' => $targetSchedule['started_at'],
            'player_id' => $draftCard['player_id'],
            'summary_position' => $draftCard['position'],
            'user_name' => User::whereId($this->user->id)->value('name'),
            'selection_level' => $totalLevel,
            'selection_cost' => $totalCost,
            'selection_point' => $totalPrice
          ], $ranDomselections)
        );
        // 4. draft_logs 기록
        Schema::connection('log')->disableForeignKeyConstraints();
        DraftLog::create(
          [
            'user_plate_card_id' => $userPlateCard['id'],
            'draft_season_id' => $draftCard['season_id'],
            'draft_team_id' => $draftCard['team_id'],
            'schedule_id' => $targetSchedule['id'],
            'origin_started_at' => $targetSchedule['started_at'],
            'schedule_status' => $targetSchedule['status'],
            'card_grade' => CardGrade::NONE,
            'status' => DraftCardStatus::UPGRADING,
          ]
        );

        // 5. user plate card 상태 upgrading로 변경
        $userPlateCard->status = DraftCardStatus::UPGRADING;
        $userPlateCard->save();
      }

      DB::commit();
    } catch (Throwable $e) {
      DB::rollback();

      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }
    $result = '5개의 plate_card 구매와 강화 완료 >> ' . $ids;

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function cardPreview()
  {
    $storage = Storage::disk('dev');
    $storageUrl = 'https://storage.b2ggames.net/fantasy-soccer';
    $headShots = $storage->allFiles('headshots');
    return view('card_preview', compact('headShots', 'storageUrl'));
  }


  public function deleteJoinRecord($_gameId)
  {
    DB::beginTransaction();
    try {
      $gameJoin = GameJoin::where([
        ['user_id', $this->user->id],
        ['game_id', $_gameId]
      ])->first();

      if (is_null($gameJoin)) {
        throw new Exception('참여한 게임이 아님');
      }
      $gameLineupQuery = GameLineup::where('game_join_id', $gameJoin->id);

      $gameLineupQuery->clone()->get()->map(function ($item) {
        __endUserPlateCardLock($item->user_plate_card_id, GradeCardLockStatus::INGAME, $item->schedule_id);
      });

      $gameLineupQuery->clone()->forceDelete();
      $gameJoin->forceDelete();

      DB::commit();
      $result = 'ID ' . $_gameId . '의 게임참여가 취소되었습니다.';
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
}

<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Squad\PlayerChangeStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Opta\YesNo;
use App\Enums\ParserMode;
use App\Enums\PlateCardFailLogType;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Season;
use App\Models\data\Squad;
use App\Models\game\Game;
use App\Models\game\PlateCard;
use App\Models\log\PlateCardFailLog;
use App\Models\log\StatusActiveChangedPlayer;
use DB;
use Exception;
use Schema;
use Str;

// https://api.performfeeds.com/soccerdata/squads/1vmmaetzoxkgg1qf6pkpfmku0k?tmcl=8l3o9v8n8tva0bb2cds2dhatw&_fmt=json&_rt=b&detailed=yes
// 한 playerId에 shirtNumber, active 상태가 각각 다를 수 있음.
class TM3SquadsPlayersParser extends BaseOptaParser
{
  use FantasyMetaTrait;

  protected const REQUEST_COUNT_AT_ONCE = 20;

  protected $activePlateCards = [];

  protected $restoreSqaudSeasons = [];

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'squads';
    $this->feedNick = 'TM3';
    $this->restoreSqaudSeasons = Season::currentSeasons()->pluck('id')->toArray();
    // $this->activePlateCards = PlateCard::currentSeason()->get(['player_id', 'team_id', 'season_id'])->keyBy('player_id')->toArray();
  }

  private function restoreSquad()
  {
    Squad::withTrashed()->whereIn('season_id', $this->restoreSqaudSeasons)->restore();
  }


  private function appendPlateCardsToCheck($_seasons)
  {
    foreach ($this->restoreSqaudSeasons as $targetIdx => $targetSeasonId) {
      foreach ($_seasons as $idx => $seasonId) {
        if ($seasonId == $targetSeasonId) {
          unset($this->restoreSqaudSeasons[$targetIdx]);
        }
      }
    }
    $cards = PlateCard::whereIn('season_id', $_seasons)->get(['player_id', 'team_id', 'season_id'])->keyBy('player_id')->toArray();
    $this->activePlateCards = array_merge($this->activePlateCards, $cards);
  }

  protected function checkPlateCards($_checkPlayer)
  {
    /**
     * 현재시즌의 active=yes인 deleted_at되지 않은 plate card와 동일 시즌에 해당하는 player 데이터가 squads로 들어오는지를 체크한다.
     */

    if (isset($this->activePlateCards[$_checkPlayer['player_id']])) {
      if ($this->activePlateCards[$_checkPlayer['player_id']]['season_id'] === $_checkPlayer['season_id']) {
        unset($this->activePlateCards[$_checkPlayer['player_id']]);
      }
    }
  }

  protected function correctOptaMisstake()
  {
    logger('correct opta mistak count:' . count($this->activePlateCards));
    foreach ($this->activePlateCards as $playerId => $elements) {
      $seasonId = $elements['season_id'];
      $teamId = $elements['team_id'];
      Schema::connection('log')->disableForeignKeyConstraints();
      DB::beginTransaction();
      try {
        $missedPlayer = Squad::withTrashed()->where(function ($query) use ($playerId, $seasonId, $teamId) {
          return $query->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId);
        })->first();
        if ($missedPlayer) {
          $missedPlayer->active = YesNo::NO;
          $missedPlayer->save();
        }
        DB::commit();

        $log = StatusActiveChangedPlayer::where(function ($query)   use ($playerId, $seasonId, $teamId) {
          return $query->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId);
        })->first();
        if ($log) {
          $log->changed_type = PlayerChangeStatus::OPTAMISTAKE;
          $log->save();
        }
      } catch (Exception $e) {
        logger($e);
        logger('증발한 선수 active 값 변경 실패');
        DB::rollBack();
      } finally {
        Schema::connection('log')->enableForeignKeyConstraints();
      }
    }
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'squad') {
      foreach ($_value as $squad) {
        $commonAttrs = [];
        if (!isset($squad['person'])) {
          __loggerEx($this->feedType, 'person not set');
          continue;
        }

        $person = $squad['person'];
        unset($squad['person']);
        // $commonAttrs = $squad;
        foreach ($squad as $key => $value) {
          $commonAttrs[$this->correctKeyName('squad', $key)] = $value;
        }

        foreach ($person as $value) {
          $value['playerId'] = $value['id'];
          $value['firstName'] = $value['firstName'] ?? '.';
          $value['lastName'] = $value['lastName'] ?? '.';
          $value['playerName'] = Str::of($value['firstName'] . ' ' . $value['lastName'])->trim()->toString();

          unset($value['id']);
          $this->appendTargetSpecifiedAttrsByIndex(
            'person',
            implode('_', [$commonAttrs['tournamentCalendarId'], $commonAttrs['contestantId'], $value['playerId']]),
            array_merge($commonAttrs, $value)
          );
          // ->
          $this->checkPlateCards(
            [
              'player_id' => $value['playerId'],
              'team_id' => $commonAttrs['contestantId'],
              'season_id' => $commonAttrs['tournamentCalendarId'],
            ]
          );
          // <-
        }
      }
    }
  }


  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    //do nothing
  }


  protected function getAllIds()
  {

    return Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::BEFORE, SeasonWhenType::FUTURE]);
  }

  protected function resetFailLog()
  {
    // overactive
    PlateCardFailLog::where(
      [
        ['fail_type', PlateCardFailLogType::OVERACTIVE],
        ['done', false],
      ]
    )->get()
      ->map(function ($failPlayer) {
        Squad::where('player_id', $failPlayer['player_id'])
          ->forceDelete();
        $failPlayer->delete();
      });

    // overcard
    PlateCardFailLog::where('fail_type', 'overcard')->doesntHave('plateCard')
      ->get()->map(function ($item) {
        $item->delete();
      });
  }

  protected function getDailyIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::FUTURE]);
  }

  protected function getConditionallyIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::FUTURE]);
  }

  protected function getAvailableSeasons($_responses)
  {
    $pattern = '/^\/\?tmcl=([a-zA-Z0-9]*)&.*$/';
    $seasons = [];
    foreach (array_keys($_responses) as $idx => $context) {
      preg_match($pattern, $context, $matches);
      $seasons[] = $matches[1];
    }
    return $seasons;
  }

  protected function parse(bool $_act): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            $ids = $this->getAllIds();
            # code...
            break;
          case FantasySyncGroupType::DAILY:
            $ids = $this->getDailyIds();
            break;
          case FantasySyncGroupType::CONDITIONALLY:
            $ids = $this->getConditionallyIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'daily') {
          $ids = $this->getDailyIds();
        } else if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        } else if ($this->getParam('mode') === 'conditionally') {
          $ids = $this->getConditionallyIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    $this->resetFailLog();

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/squad']);
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    // $this->setKeyNameTransMap(['id'=>'playerId']);
    // $this->setKGsToCustom(['/matchDate']);
    // optaParser 설정 <<--

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);

    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx);

      $responses = $this->optaRequest($idChunk);
      $this->appendPlateCardsToCheck(
        $this->getAvailableSeasons($responses)
      );
      logger('-->>');
      logger(array_keys($responses));
      logger($this->getAvailableSeasons($responses));
      logger('<<--');


      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['person' => Squad::class],
            'conditions' => ['season_id', 'team_id', 'player_id'],
          ]
        ],
        $_act
      );
    }
    $this->restoreSquad();
    $this->correctOptaMisstake();

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,  //ex) ['common_table_name' => 'common_table_name', 'conditions' => []],
    array $_specifiedInfoToStore = null, //ex) [ [ 'specifiedInfoMap' => ['penaltyShot' => 'penalty_shots'], 'conditions' => ['matchId', 'timestamp'] ] ],
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->preProcessResponse($urlKey, $response);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
    }
  }
}

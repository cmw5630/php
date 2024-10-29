<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\DataControll\CardCQuantileUpdator;
use App\Console\Commands\DataControll\PlateCRefsUpdator;
use App\Console\Commands\DataControll\PointCQuantileUpdator;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\League;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\OptaTeamDailyStat;
use App\Models\data\Season;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefCardcQuantile;
use App\Models\meta\RefPlateCQuantile;
use App\Models\meta\RefPointcQuantile;


// https://api.performfeeds.com/soccerdata/matchstats/1vmmaetzoxkgg1qf6pkpfmku0k/ew92044hbj98z55l8rxc6eqz8?_fmt=json&_rt=b&detailed=yes
// goals의 scorerId 또는 assistPlayerId 가 players 테이블에 없는 경우가 있는 듯. 확인할 것. 또는 Foreign key로 처리하고 테스트해볼 것.
class ServiceDataChecker extends MA2MatchStatsParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'matchstats';
    $this->feedNick = 'SDC'; // MA2
  }

  private function getDailyIds(): array
  {
    //2. 현재시즌의 비서비스 경기에 대한 추가 수집(가격 변동 로직을 태우려면 반드시 필요함)
    $extraSchedules = Schedule::whereHas('league', function ($query) {
      return $query->withoutGlobalScopes()->parsingAvalilable();
    })
      ->doesntHave('oneOptaPlayerDailyStat')
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->whereHas('season', function ($query) {
        $query->currentSeasons();
      })
      ->oldest('started_at')->pluck('id')->toArray();

    $allIds = $this->getAllIds();

    return array_merge($allIds, $extraSchedules);
  }

  private function getAllIds(): array
  {
    // RefPlateCQuantile
    /**
     * @var array
     */
    $beforeSeasonIds =  Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1);
    $refSoruceSeasonId = array_column(RefPlateCQuantile::get()->toArray(), 'source_season_id');
    $needSeasonIdsA =  array_diff($beforeSeasonIds, $refSoruceSeasonId);

    // RefCardCQuantile
    /**
     * @var array
     */
    $currentSeasonIds =  Season::idsOf([SeasonWhenType::CURRENT], SeasonNameType::ALL, 1);
    $refCardCSeasonIds = array_column(RefCardcQuantile::get()->toArray(), 'playing_season_id');
    $needSeasonIdsB = array_diff($currentSeasonIds, $refCardCSeasonIds);

    // RefPointCQuantile
    $currentSeasonNames = array_unique(array_column(Season::currentSeasons()->get()->toArray(), 'name'));
    $refSeasonNames = array_keys(RefPointcQuantile::get('playing_season_name')->keyBy('playing_season_name')->toArray());
    $needSeasonNames = array_diff($currentSeasonNames, $refSeasonNames);
    $needSeasonIdsC =  array_column(Season::whereIn('name', $needSeasonNames)->get()->toArray(), 'id');
    $totalSeasonIds =  array_unique(array_merge($needSeasonIdsA, $needSeasonIdsB, $needSeasonIdsC));

    $targetLeagueIds = array_column(League::withoutGlobalScopes()->whereHas('seasons', function ($query) use ($totalSeasonIds) {
      $query->whereIn('id', $totalSeasonIds);
    })->get()->toArray(), 'id');


    // 1. 현재시즌 제외 조건 필요.
    $baseSchedules = Schedule::whereHas('league', function ($query) use ($targetLeagueIds) {
      return $query->withoutGlobalScopes()->parsingAvalilable()->whereIn('id', $targetLeagueIds);
      // 스탯이 쌓이는 매치 상태만
    })->whereHas('season', function ($query) {
      $query->currentSeasons(false);
    })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->oldest('started_at')->pluck('id')->toArray();

    return $baseSchedules;
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
          case FantasySyncGroupType::CONDITIONALLY:
            $ids = $this->getDailyIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    // optaParser 설정 -->>
    $this->setKeysToIgnore([
      // 'matchDetails',
      // 'goal', 
      // 'card', 
      // 'substitute', 
      'VAR',
      // 'lineUp', // player
      // 'matchDetailsExtra',
      // 'contestant'
    ]);
    $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setGlueChildKeys([]);
    // optaParser 설정 <<--
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'matchId', 'touches' => 'touchesOpta']);

    // $match_ids = ['a9044tbpv83gxramw9iovn7ro'];
    __loggerEx($this->feedType, 'schedule total count : ' . count($ids));

    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx . ' / ' . $totalChucks);

      $responses = $this->optaRequest($idChunk);
      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['player' => OptaPlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id']
          ],
          [
            'specifiedInfoMap' => ['teamStats' => OptaTeamDailyStat::class],
            'conditions' => ['schedule_id', 'team_id']
          ],
          [
            'specifiedInfoMap' => [self::playerDailySpecifiedKey => PlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id'] // update condidions
          ],
          // [
          //   'specifiedTableMap' => ['penaltyShot' => 'penalty_shots'],
          //   'conditions' => ['matchId', 'playerId', 'timeMinSec']
          // ],
          // [
          //   'specifiedTableMap' => ['goal' => 'goals'],
          //   'conditions' => ['matchId', 'scorerId', 'timeMinSec']
          // ],
          // [
          //   'specifiedTableMap' => ['card' => 'cards'],
          //   'conditions' => ['matchId', 'playerId', 'timeMinSec']
          //   // timestamp 정보가 없을 수 있음.
          // ],
          // [
          //   'specifiedTableMap' => ['substitute' => 'substitutions'],
          //   'conditions' => ['matchId', 'playerOnId', 'playerOffId']
          // ]
        ],
        $_act
      );
    }
    (new PlateCRefsUpdator())->start();
    (new PointCQuantileUpdator())->start();
    (new CardCQuantileUpdator())->start();
    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}

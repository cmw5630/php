<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\ParserMode;
use App\Exceptions\Custom\Parser\OTPDataMissingException;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\OptaTeamSeasonStat;
use App\Models\data\Schedule;
use App\Models\game\PlayerDailyStat;
use Arr;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
use Str;

// http://api.performfeeds.com/soccerdata/seasonstats/1vmmaetzoxkgg1qf6pkpfmku0k?tmcl=4vg1rldzyr8uev96z173gkxgq&ctst=3st9rj62b3qnni6uolw0lwaqn&_rt=b&_fmt=json
class TM4SeasonStats extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;
  protected $isDaily = true;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'seasonstats';
    $this->feedNick = 'TM4';
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'stat') {
      $teamStatData = [];
      foreach ($_value as $items) {
        $colName = $this->normalizeColumnName($items['name']);
        $value = $items['value'];
        $teamStatData[$colName] = $value;
      }

      // $extraTeamStats = OptaPlayerDailyStat::where([
      //   ['season_id', $this->getCommonAttrs()['tournamentCalendarId']],
      //   ['team_id', $this->getCommonAttrs()['contestantId']]
      // ])
      //   ->selectRaw('sum(saves) as saves,
      //    sum(saved_ibox) as saved_ibox,
      //     sum(penalty_save) as penalty_save,
      //      sum(good_high_claim) as good_high_claim,
      //       sum(dive_catch) as dive_catch,
      //       sum(accurate_keeper_sweeper) as accurate_keeper_sweeper,
      //       sum(att_freekick_goal) as freekick_goal,
      //       sum(big_chance_created) as big_chance_created,
      //       sum(accurate_cross) as accurate_cross,
      //       sum(successful_final_third_passes) as successful_final_third_passes,
      //       sum(offside_provoked) as offside_provoked')
      //   ->first()
      //   ->toArray();
      //
      // foreach ($extraTeamStats as $key => $val) {
      //   if (is_null($val)) {
      //     unset($extraTeamStats[$key]);
      //   }
      // }
      //
      // $teamStatData = array_merge($teamStatData, $extraTeamStats);

      $this->setTargetSpecifiedAttrs('teamStat', $teamStatData);
    } else if ($_key === 'player') {
      foreach ($_value as $player) {
        $stat = [];
        $playerStatData = [];
        if (isset($player['stat'])) {
          $stat = $player['stat'];
          unset($player['stat']);
        }
        $playerStatData['playerPosition'] = $player['position'] ?? null;

        try {
          $playerStatData['playerId'] = $player['id'];
        } catch (Exception $e) {
          // LogEx::error($this->feedType, 'Player Id is null');
          report(new OTPDataMissingException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
          continue;
        }
        $playerStatData['playerShirtNumber'] = $player['shirtNumber'] ?? null;
        $playerStatData['playerFirstName'] = $player['firstName'] ?? null;
        $playerStatData['playerLastName'] = $player['lastName'] ?? null;
        $playerStatData['playerShortFirstName'] = $player['shortFirstName'] ?? null;
        $playerStatData['playerShortLastName'] = $player['shortLastName'] ?? null;
        $playerStatData['matchName'] = $player['matchName'] ?? null;

        $playerId = $player['id'];
        foreach ($stat as $items) {
          $colName = $this->normalizeColumnName($items['name']);
          $value = $items['value'];
          $playerStatData[$colName] = $value;
        }
        $extraPlayerStats = OptaPlayerDailyStat::where([
          ['season_id', $this->getCommonAttrs()['tournamentCalendarId']],
          ['player_id', $playerId]
        ])->selectRaw('
          sum(mins_played) as minsPlayed,
          sum(fantasy_point) as fantasyPoint,
          round((sum(accurate_pass) / sum(total_pass) * 100), 2) as passingAccuracy
        ')
          ->first()
          ->toArray();

        foreach ($extraPlayerStats as $key => $val) {
          if (is_null($val)) {
            unset($extraPlayerStats[$key]);
          }
        }
        $playerStatData = array_merge($playerStatData, $extraPlayerStats);

        $perColumns = array_values(array_unique(Arr::collapse(config('stats.categories.player_per'))));
        $perColumns = array_merge($perColumns, ['fantasy_point']);
        if (isset($playerStatData['minsPlayed']) && $playerStatData['minsPlayed'] > 0) {
          foreach ($perColumns as $column) {
            $column = Str::camel($column);
            if (!isset($playerStatData[$column])) {
              continue;
            }
            $playerStatData[$column . 'Per'] = BigDecimal::of($playerStatData[$column])->dividedBy(BigDecimal::of($playerStatData['minsPlayed'])->dividedBy(
              90,
              10,
              RoundingMode::DOWN
            ), 2, RoundingMode::HALF_UP);
          }
        }
        unset($playerStatData['minsPlayed']);
        unset($playerStatData['fantasyPoint']);

        $this->appendTargetSpecifiedAttrsByIndex(
          'playerStat',
          $playerId,
          $playerStatData
        );
      }
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    //do nothing
  }


  private function updateSomeColumns()
  {
    // AVG - fantasy_point, rating updating!
    PlayerDailyStat::query()
      ->gameParticipantPlayer()
      ->selectRaw(
        'player_id, 
        team_id, 
        season_id, 
        AVG(fantasy_point) as fantasy_point, 
        AVG(rating) as rating',
      )
      ->when($this->isDaily, function ($query) {
        $query->whereHas('season', function ($seasonQuery) {
          return $seasonQuery->currentSeasons();
        });
      })->groupBy(['season_id', 'team_id', 'player_id'])
      ->get()->map(function ($row) {
        $statRow = OptaPlayerSeasonStat::where([
          'season_id' => $row['season_id'],
          'team_id' => $row['team_id'],
          'player_id' => $row['player_id'],
        ])->first();
        if ($statRow) {
          $statRow->fantasy_point = $row->fantasy_point;
          $statRow->rating = $row->rating;
          $statRow->save();
        }
      });

    PlayerDailyStat::query()
      ->gameParticipantPlayer()
      ->selectRaw(
        'team_id, 
        season_id, 
        AVG(rating) as rating',
      )
      ->when($this->isDaily, function ($query) {
        $query->whereHas('season', function ($seasonQuery) {
          return $seasonQuery->currentSeasons();
        });
      })->groupBy(['season_id', 'team_id'])
      ->get()->map(function ($row) {
        $statRow = OptaTeamSeasonStat::where([
          'season_id' => $row['season_id'],
          'team_id' => $row['team_id'],
        ])->first();
        if ($statRow) {
          $statRow->rating = $row->rating;
          $statRow->save();
        }
      });
  }

  protected function getBaseIds($_current = true, $_rescently = false): array
  {
    // season team ids
    $ids = [];
    // 파라미터 두개 들어가는 케이스를 따로 만들어야 해서 그냥 tmcl과 ctst를 통째로 하나의 파라미터로 만듦
    Schedule::when($_current, function ($query) {
      $query->whereHas('season', function ($seasonQuery) {
        return $seasonQuery->currentSeasons();
      });
    })->when($_rescently, function ($query) {
      $query->whereBetween('started_at', [
        Carbon::now()->subDay(),
        Carbon::now(),
      ]);
    })
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->get(['id', 'season_id', 'home_team_id', 'away_team_id'])
      ->map(function ($item) use (&$ids) {
        foreach ([ScheduleWinnerStatus::HOME, ScheduleWinnerStatus::AWAY] as $position) {
          $teamId = $item->{$position . '_team_id'};
          $param = 'tmcl=' . $item->season_id . '&ctst=' . $teamId;
          if (!in_array($param, $ids)) {
            $ids[] = $param;
          }
        }
      });

    return $ids;
  }

  protected function getAllids($_current = true): array
  {
    $this->isDaily = false;
    return $this->getBaseIds(false);
  }


  protected function getDailyIds(): array
  {
    return $this->getBaseIds(true, false);
  }

  protected function getElasticIds(): array
  {
    return $this->getBaseIds(true, true);
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
          case FantasySyncGroupType::ELASTIC:
            $ids = $this->getElasticIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        } else if ($this->getParam('mode') === 'daily') {
          $ids = $this->getDailyIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    // optaParser 설정 -->>
    $this->setKeyNameTransMap(array_merge(
      $this->getKeyNameTransMap(),
      [
        'touches' => 'touches_opta',
        'redCard_2ndYellow' => 'red_card',
        'unsuccessfullay_offs' => 'unsuccessful_lay_offs'
      ]
    ));
    $this->setKGsToCustom(['contestant/stat', '/player']);
    // optaParser 설정 <<--

    // $ids = ['tmcl=87typal84j1zls3ushwjsox78&ctst=66bsnl0zjb7l5akwo00h0y5me'];

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);

      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['teamStat' => OptaTeamSeasonStat::class],
            'conditions' => ['season_id', 'team_id']
          ],
          [
            'specifiedInfoMap' => ['playerStat' => OptaPlayerSeasonStat::class],
            'conditions' => ['season_id', 'player_id']
          ]
        ],
        $_act
      );
    }
    $this->updateSomeColumns();
    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}

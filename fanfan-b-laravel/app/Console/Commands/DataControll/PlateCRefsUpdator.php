<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Season;
use App\Models\meta\RefPlateCPlayer;
use App\Models\meta\RefPlateCQuantile;
use App\Models\meta\RefPlateGradePrice;
use DB;
use Exception;

class PlateCRefsUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $beforeSeasonIds;

  protected $currentBeforeSeasonIdMap;

  protected $isCompleted = false;

  public function __construct()
  {
    $this->feedNick = 'PLTCQU';
    $this->beforeSeasonIds = Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1);
    $this->currentBeforeSeasonIdMap  = Season::getBeforeCurrentMapCollection();
  }


  public function updatePlateCRef()
  {
    OptaPlayerDailyStat::selectRaw(
      'player_id, 
      season_id, 
      SUM(power_ranking) power_ranking_total, 
      SUM(game_started) game_started_total,
      SUM(total_sub_on) total_sub_on_total,
      COUNT(player_id) entry_total',
    )->where('status', ScheduleStatus::PLAYED) // Played 된 것만 power_ranking이 계산되어있으므로
      ->whereIn('season_id', $this->beforeSeasonIds) // 이전 시즌에 대해서만
      ->whereHas('season', function ($query) {
        return $query->whereHas('league', function ($_query) {
          return $_query->withoutGlobalScopes();
        });
      })
      ->groupBy(['player_id', 'season_id'])
      ->get()->flatMap(
        function ($statsGrouped) {
          foreach ($this->currentBeforeSeasonIdMap as $idx => $seasonMap) {
            if ($statsGrouped['season_id'] === $seasonMap['before_id']) {
              $statsGrouped['league_id'] = $seasonMap['league_id'];
              $statsGrouped['source_season_id'] = $seasonMap['before_id'];
              unset($statsGrouped['season_id']);
              break;
            }
          }
          // if (!isset($statsGrouped['source_season_id'])) {
          //   logger($statsGrouped->toArray());
          //   return;
          // }

          $statsGrouped['plate_c'] = $this->getPlateC($statsGrouped->toArray());
          $row = $statsGrouped->toArray();

          RefPlateCPlayer::updateOrCreateEx(
            [
              'player_id' => $row['player_id'],
              'source_season_id' => $row['source_season_id'],
            ],
            $row,
            false,
            true,
          );
        }
      );
  }

  public function getPlateC($midSourcData)
  {
    // logger($midSourcData);
    if ($playCount = $midSourcData['game_started_total'] + $midSourcData['total_sub_on_total']) {
      $only_entry = $midSourcData['entry_total'] - $playCount;
      $total_g = $midSourcData['entry_total'];
      $start_per = $midSourcData['game_started_total'] / $total_g;
      $sub_per = $midSourcData['total_sub_on_total'] / $total_g;
      $start_d = $midSourcData['game_started_total'] * $start_per;
      $sub_d = ($midSourcData['total_sub_on_total'] / 2) * $sub_per;
      $entry_d = -$only_entry * 0.135;
      $game_c = ($start_d + $sub_d) * 0.135;
      $game_d = __setDecimal($game_c + $entry_d, 3, 'round');
      $avg_pw  = __setDecimal($midSourcData['power_ranking_total'] / $total_g, 2, 'round');
      $plate_c = __setDecimal($avg_pw + $game_d, 2, 'round');

      // logger(sprintf('game_started_total=%s', $midSourcData['game_started_total']));
      // logger(sprintf('total_sub_on_total=%s', $midSourcData['total_sub_on_total']));
      // logger(sprintf('only_entry=%s', $only_entry));
      // logger(sprintf('total_g=%s', $total_g));
      // logger(sprintf('start_per=%s', $start_per));
      // logger(sprintf('sub_per=%s', $sub_per));
      // logger(sprintf('start_d=%s', $start_d));
      // logger(sprintf('sub_d=%s', $sub_d));
      // logger(sprintf('entry_d=%s', $entry_d));
      // logger(sprintf('game_c=%s', $game_c));
      // logger(sprintf('game_d=%s', $game_d));
      // logger(sprintf('avg_pw=%s', $avg_pw));
      // logger(sprintf('plate_c=%s', $plate_c));

      return $plate_c;
      // $_plateCardrow->plate_c = $plate_c;
    } else { // entry에 있지만 한번도 출전하지 않은 선수 처리
      return -20;
    }
  }

  public function getQuantilePercentTable()
  {
    return RefPlateGradePrice::get(['grade', 'percentile_point'])->toArray();
  }

  public function updatePlateCQuantileRef()
  {
    RefPlateCPlayer::selectRaw(
      'league_id,
      source_season_id,
      plate_c,
      ROW_NUMBER() over(PARTITION BY source_season_id order by plate_c desc) as nrank'
    )->whereIn('source_season_id', Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1))
      ->get()->groupBy(['source_season_id'])->map(function ($item) {
        $count = $item->count();
        $item = $item->keyBy('nrank');
        if ($count === 0) {
          return;
        };

        $row = [];
        foreach ($this->currentBeforeSeasonIdMap as $idx => $map) {
          if ($map['before_id'] === $item[1]['source_season_id']) {
            $row = [
              'league_id' => $item[1]['league_id'],
              'source_season_id' => $item[1]['source_season_id'],
              'price_init_season_id' => $map['current_id'],
            ];
            break;
          }
        }
        if (!isset($row['source_season_id'])) {
          logger('bug!!!!!!!!!!!!!!!!!!!!!!!!!');
          return;
        }

        foreach ($this->getQuantilePercentTable() as $percentMap) {
          if ((float)$percentMap['percentile_point'] === (float)0) {
            $quantileValue = 999; // 무한대
          } else {
            $quantileValue = $item[$this->getRankFromPercent($count, $percentMap['percentile_point'])]['plate_c'];
          }
          $row['quantile' . '_' . $percentMap['grade']] = $quantileValue;
        }
        logger($row);
        RefPlateCQuantile::updateOrCreateEx(
          [
            'source_season_id' => $row['source_season_id']
          ],
          $row,
          false,
          true,
        );
      });
  }

  public function getRankFromPercent($_count, $_percent)
  {
    return __setDecimal(($_count * ($_percent / 100)), 0, 'round');
  }


  public function start(): bool
  {
    /**
     * MA2 수집이 선행되어야함.
     * 플레이트 가격을 산출하기 위해 필요한 선수별 이전시즌의 power_rakning 및 출전 수 관련 데이터 집계를 plate_c_refs에 저장
     */


    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            # code...
            break;
          case FantasySyncGroupType::DAILY:
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

    DB::beginTransaction();
    try {

      $this->updatePlateCRef();

      $this->updatePlateCQuantileRef();

      DB::commit();
      logger('update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('실패(RollBack)');
      return false;
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}

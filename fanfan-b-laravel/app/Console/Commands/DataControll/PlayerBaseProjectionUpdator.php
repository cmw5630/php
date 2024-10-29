<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\RefPlayerBaseProjection;
use DB;
use Exception;

class PlayerBaseProjectionUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $targetSeasonIds;

  protected $currentBeforeSeasonIdMap;

  protected $isCompleted = false;

  public function __construct()
  {
    $this->feedNick = 'PBPU';
    $this->targetSeasonIds = Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::BEFORE], SeasonNameType::ALL, 2);
    $this->currentBeforeSeasonIdMap  = Season::getBeforeCurrentMapCollection();
  }


  public function update()
  {
    $schedule = Schedule::selectRaw(
      "id,  
         league_id,
         home_team_id,
         away_team_id"
    );

    foreach ([ScheduleWinnerStatus::HOME, ScheduleWinnerStatus::AWAY] as $teamSide) {
      OptaPlayerDailyStat::selectRaw(
        sprintf("league_id,
            player_id,
            '%s' as team_side,
            SUM(fantasy_point) as fantasy_point_total,
            SUM(mins_played) as mins_played_total,
            SUM(game_started) as game_started_total,
            SUM(total_sub_on) as total_sub_on_total
        ", $teamSide)
      )->whereIn('season_id', $this->targetSeasonIds)
        ->joinSub($schedule, 'schedule', function ($join) use ($teamSide) {
          $optTableName = OptaPlayerDailyStat::getModel()->getTable();
          $join->on($optTableName . '.schedule_id', 'schedule.id');
          $join->on($optTableName . '.team_id',  sprintf('schedule.%s_team_id', $teamSide));
        })->groupBy(['league_id', 'player_id'])->get()
        ->map(function ($item) {
          $minPlayedTotal = $item['mins_played_total'];
          $fantasyPointTotal = $item['fantasy_point_total'];
          $playedCount = ($item['game_started_total'] + $item['total_sub_on_total']);
          if ($minPlayedTotal != 0 && $playedCount != 0) {
            $item['fp_per_min'] = $fantasyPointTotal / $minPlayedTotal;
            $item['avg_played_time'] = $minPlayedTotal / $playedCount;
            $item['base_value'] = $item['fp_per_min'] * $item['avg_played_time'];
            RefPlayerBaseProjection::updateOrCreateEx(
              [
                'league_id' => $item['league_id'],
                'player_id' => $item['player_id'],
                'team_side' => $item['team_side'],
              ],
              $item->toArray(),
              false,
              true,
            );
          }
        });
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
      $this->update();
      DB::commit();
      logger('update base projection update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('update base projection update 실패!!!');
      return false;
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}

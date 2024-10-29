<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Libraries\Traits\GameTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\PerScheduleTeam;
use App\Models\game\PerSeason;
use App\Models\game\PerSeasonRound;
use App\Models\game\PerSeasonTeam;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Exception;

class HeadToHeadPerPointUpdator
{
  use FantasyMetaTrait;

  use GameTrait;

  protected $feedNick;

  protected $seasonIds = [];
  protected $isCurrent = false;

  public function __construct(array|string $_seasonIds = [])
  {
    $this->feedNick = 'HTHPPU';
    if (gettype($_seasonIds) === 'string') {
      $this->seasonIds = [$_seasonIds];
    } else {
      $this->seasonIds = $_seasonIds;
      if (empty($this->seasonIds)) {
        $this->seasonIds = Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::BEFORE], SeasonNameType::ALL, 3, [config('constant.LEAGUE_CODE.UCL'), config('constant.LEAGUE_CODE.MLS')]);
      }
    }
  }

  // 현재 종료된 Round
  private function maxFinishedRound($_seasonId)
  {
    $maxPlayedRound = Schedule::where([
      ['season_id', $_seasonId],
      ['status', ScheduleStatus::PLAYED]
    ])->max('round');

    if (is_null($maxPlayedRound) || $maxPlayedRound > 38) {
      return 38;
    }

    $this->isCurrent = true;

    $currentRoundSchedules = $this->getStatusCount(Schedule::where([
      ['season_id', $_seasonId],
      ['ga_round', $maxPlayedRound]
    ])->get()->toArray());

    if ($currentRoundSchedules['Fixture'] > 0) {
      return $maxPlayedRound - 1;
    } else {
      return $maxPlayedRound;
    }
  }

  private function baseUpdate()
  {
    foreach ($this->seasonIds as $idx => $seasonId) {
      // 1. schedule 별 team 별 position 별
      $maxRound = $this->maxFinishedRound($seasonId);
      OptaPlayerDailyStat::query()
        ->where('season_id', $seasonId)
        ->whereHas('schedule', function ($scheduleQuery) use ($maxRound) {
          $scheduleQuery->where('status', ScheduleStatus::PLAYED)
            ->when($this->isCurrent, function ($currentQuery) use ($maxRound) {
              $currentQuery->where('round', '>=', $maxRound);
            });
        })->selectRaw('season_id,schedule_id,team_id,summary_position AS position,SUM(fantasy_point) AS sum_point,SUM(mins_played) AS all_mins')
        ->groupBy(['season_id', 'schedule_id', 'team_id', 'summary_position'])
        ->get()
        ->map(function ($info) {
          $scheduleTeam['season_id'] = $info->season_id;
          $round = Schedule::whereId($info->schedule_id)->value('round');
          $scheduleTeam['round'] = $round;
          $scheduleTeam['schedule_id'] = $info->schedule_id;
          $scheduleTeam['team_id'] = $info->team_id;
          $scheduleTeam['position'] = $info->position;
          $scheduleTeam['per_fp'] = 0;
          $scheduleTeam['all_mins'] = $info->all_mins;
          if ($info->sum_point > 0 && $info->all_mins > 0) {
            $scheduleTeam['per_fp'] = BigDecimal::of($info->sum_point)->dividedBy(BigDecimal::of($info->all_mins)->dividedBy(90, 10, RoundingMode::DOWN), 1, RoundingMode::HALF_UP);
          }

          PerScheduleTeam::updateOrCreateEx(
            [
              'schedule_id' => $info->schedule_id,
              'team_id' => $info->team_id,
              'position' => $info->position,
            ],
            $scheduleTeam
          );
        });
    }
  }

  private function secondUpdate()
  {
    // 2. season 별 team 별 position 별
    PerScheduleTeam::whereIn('season_id', $this->seasonIds)
      ->selectRaw('season_id,team_id, position,SUM(per_fp) AS sum_point,SUM(all_mins) AS all_mins')
      ->groupBy(['season_id', 'team_id', 'position'])
      ->get()
      ->map(function ($info) {
        $seasonTeam['season_id'] = $info->season_id;
        $seasonTeam['team_id'] = $info->team_id;
        $seasonTeam['position'] = $info->position;
        $seasonTeam['per_fp'] = 0;
        $seasonTeam['all_mins'] = $info->all_mins;
        if ($info->sum_point > 0 && $info->all_mins > 0) {
          $seasonTeam['per_fp'] = BigDecimal::of($info->sum_point)->dividedBy(BigDecimal::of($info->all_mins)->dividedBy(90, 10, RoundingMode::DOWN), 1, RoundingMode::HALF_UP);
        }

        PerSeasonTeam::updateOrCreateEx(
          [
            'season_id' => $info->season_id,
            'team_id' => $info->team_id,
            'position' => $info->position,
          ],
          $seasonTeam
        );
      });

    // 3. season 별 round 별 position 별
    PerScheduleTeam::whereIn('season_id', $this->seasonIds)
      ->selectRaw('season_id,round, position,SUM(per_fp) AS sum_point,SUM(all_mins) AS all_mins')
      ->groupBy(['season_id', 'round', 'position'])
      ->get()
      ->map(function ($info) {
        $seasonRound['season_id'] = $info->season_id;
        $seasonRound['round'] = $info->round;
        $seasonRound['position'] = $info->position;
        $seasonRound['per_fp'] = 0;
        $seasonRound['all_mins'] = $info->all_mins;
        if ($info->sum_point > 0 && $info->all_mins > 0) {
          $seasonRound['per_fp'] = BigDecimal::of($info->sum_point)->dividedBy(BigDecimal::of($info->all_mins)->dividedBy(90, 10, RoundingMode::DOWN), 1, RoundingMode::HALF_UP);
        }

        PerSeasonRound::updateOrCreateEx(
          [
            'season_id' => $info->season_id,
            'round' => $info->round,
            'position' => $info->position,
          ],
          $seasonRound
        );
      });
  }


  private function thirdUpdate()
  {
    // 4. season 별 position 별
    PerSeasonRound::whereIn('season_id', $this->seasonIds)
      ->selectRaw('season_id,position,SUM(per_fp) AS sum_point,SUM(all_mins) AS all_mins')
      ->groupBy(['season_id', 'position'])
      ->get()
      ->map(function ($info) {
        $season['season_id'] = $info->season_id;
        $season['position'] = $info->position;
        $season['per_fp'] = 0;
        $season['all_mins'] = $info->all_mins;
        if ($info->sum_point > 0 && $info->all_mins > 0) {
          $season['per_fp'] = BigDecimal::of($info->sum_point)->dividedBy(BigDecimal::of($info->all_mins)->dividedBy(90, 10, RoundingMode::DOWN), 1, RoundingMode::HALF_UP);
        }

        PerSeason::updateOrCreateEx(
          [
            'season_id' => $info->season_id,
            'position' => $info->position,
          ],
          $season
        );
      });
  }


  private function updateTable()
  {
    $this->baseUpdate();
    $this->secondUpdate();
    $this->thirdUpdate();
    // $this->delOldData();
  }


  public function start(): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            break;
          case FantasySyncGroupType::DAILY:
            break;
          default:
            break;
        }
    }

    DB::beginTransaction();
    try {
      logger('start H2H perPoint update');
      $this->updateTable();
      DB::commit();
      logger(' H2H perPoint update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('update  H2H perPoint 실패(RollBack)');
      throw $e; // 관련된 모두 롤백되어야 함.
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}

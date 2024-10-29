<?php

namespace App\Console\Commands\DataControll;

use App\Models\data\BrSchedule;
use App\Models\data\Schedule;
use App\Models\game\GamePossibleSchedule;
use Carbon\Carbon;
use DB;
use Exception;

class GaRoundUpdator
{
  protected $scheduleId;

  private $seasonRoundMap;

  private $roundTimes;

  public function __construct()
  {
    $seasonRoundMap = [];
    Schedule::whereHas('season', function ($query) {
      $query->currentSeasons();
    })->whereNotNull('round')
      ->whereNotIn(
        'league_id',
        [
          config('constant.LEAGUE_CODE.UCL'),
          config('constant.LEAGUE_CODE.MLS'),
          config('constant.LEAGUE_CODE.LPA')
        ]
      )
      ->whereNull('ended_at')
      ->get()
      ->groupBy('round')
      ->map(function ($groupedItem) use (&$seasonRoundMap) {
        foreach ($groupedItem->toArray() as $i => $item) {
          $seasonRoundMap[$item['season_id']][$item['round']][] = $item['started_at'];
        };
      });

    $this->seasonRoundMap = $seasonRoundMap;

    $roundTimes = [];
    foreach ($this->seasonRoundMap as $seasonId => $roundSet) {
      foreach ($roundSet as $round => $xTimeSet) {
        sort($xTimeSet);
        $centerTimeIdx = round((count($xTimeSet) - 1)  / 2);
        $roundTimes[$seasonId][$round] = $xTimeSet[$centerTimeIdx];
      }
    }

    $this->roundTimes = $roundTimes;
  }


  private function getApproxyMatedRound($_seasonId, $_startedAt)
  {
    $cTimes = [];
    $targetRound = 0;
    $minValue = 999;
    if (!isset($this->roundTimes[$_seasonId])) return null;
    foreach ($this->roundTimes[$_seasonId] as $round => $time) {
      $newValue = $cTimes[$round] = Carbon::parse($_startedAt)->diffInDays($time);
      if ($newValue < $minValue) {
        $targetRound = $round;
        $minValue = $newValue;
      }
    }

    return $targetRound;
  }

  private function updateGaRound()
  {
    Schedule::whereBetween('started_at', [Carbon::now(), Carbon::now()->addDays(60)])->get()
      ->map(function ($item) {
        $seasonId = $item['season_id'];
        $startedAt = $item['started_at'];
        $newGaRound = $this->getApproxyMatedRound($seasonId, $startedAt);
        if ($newGaRound != null && $item->ga_round != $newGaRound) {
          $item->ga_round = $newGaRound;
          $item->save();
        }
      });
  }

  private function updateGamePossibleScheduleEventId()
  {
    GamePossibleSchedule::withTrashed()->whereNull('br_schedule_id')->get()
      ->map(function ($item) {
        $brScheduleItem = BrSchedule::where('opta_schedule_id', $item['schedule_id'])->first();
        if ($brScheduleItem === null) {
          return;
        }
        logger($brScheduleItem->toArray());
        $item['br_schedule_id'] = $brScheduleItem['sport_event_id'];
        $item->save();
      });
  }



  public function update()
  {
    DB::beginTransaction();
    try {
      $this->updateGaRound();
      $this->updateGamePossibleScheduleEventId();
      DB::commit();
      logger('ga round update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger('ga round update 실패(RollBack)');
      throw $e; // 관련된 모두 롤백되어야 함.
    }
  }
}

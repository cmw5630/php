<?php

namespace App\Observers;

use App\Events\ScheduleBoardEvent;
use App\Models\data\Schedule;
use Illuminate\Support\Facades\Redis;

class ScheduleObserver
{
  /**
   * Handle the Schedule "created" event.
   *
   * @param \App\Models\data\Schedule $schedule
   * @return void
   */
  public function created(Schedule $schedule)
  {
    //
  }

  /**
   * Handle the Schedule "updated" event.
   *
   * @param \App\Models\data\Schedule $schedule
   * @return void
   */
  public function updated(Schedule $schedule)
  {
    // event(new ScheduleBoardEvent($schedule->toArray()));
  }

  /**
   * Handle the Schedule "deleted" event.
   *
   * @param \App\Models\data\Schedule $schedule
   * @return void
   */
  public function deleted(Schedule $schedule)
  {
    //
  }

  /**
   * Handle the Schedule "restored" event.
   *
   * @param \App\Models\data\Schedule $schedule
   * @return void
   */
  public function restored(Schedule $schedule)
  {
    //
  }

  /**
   * Handle the Schedule "force deleted" event.
   *
   * @param \App\Models\data\Schedule $schedule
   * @return void
   */
  public function forceDeleted(Schedule $schedule)
  {
    //
  }
}

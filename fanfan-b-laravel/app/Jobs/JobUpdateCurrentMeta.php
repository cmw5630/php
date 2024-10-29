<?php

namespace App\Jobs;

use App\Console\Commands\DataControll\PlayerCurrentMetaRefUpdator;
use App\Models\data\Schedule;
use App\Models\game\PlateCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobUpdateCurrentMeta implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */

  protected $datas;

  public function __construct($_datas)
  {
    $this->datas = $_datas;
    $this->onConnection('sync')->beforeCommit();
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $schedule = Schedule::find($this->datas['commonRowOrigin']['schedule_id']);
    $playerIds = PlateCard::currentSeason()
      ->where(function ($query) use ($schedule) {
        $query->where('team_id', $schedule['home_team_id'])
          ->orWhere('team_id', $schedule['away_team_id']);
      })->pluck('player_id')->toArray();
    (new PlayerCurrentMetaRefUpdator($playerIds))->update();
  }
}

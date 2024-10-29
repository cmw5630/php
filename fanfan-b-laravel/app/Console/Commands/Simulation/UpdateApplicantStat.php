<?php

namespace App\Console\Commands\Simulation;

use App\Console\Commands\DataControll\SimulationStatAggrUpdator;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Models\simulation\SimulationSchedule;
use Illuminate\Console\Command;
use DB;
use Throwable;

class UpdateApplicantStat extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'SIM:applicant-stat {--server=} {--mode=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $options = $this->options();
    $server = $options['server'] ?? 'europe';
    $mode = $options['mode'];

    $schedules = SimulationSchedule::whereHas('season', function ($query) use ($server) {
        $query->where('server', $server)
          ->currentSeasons();
      })
      ->where([
        'status' => SimulationScheduleStatus::PLAYED,
        'is_rank_completed' => false
      ])
      ->when($mode, function ($whenMode, $mode) {
        if ($mode === 'season') {
          // 안함
        } else if ($mode === 'day') {
          // 하루
          $whenMode->whereDate('started_at', now()->toDateString());
        }
      }, function ($query) {
        // 일반 : 한시간
        $query->where('started_at', '>=', now()->subHour());
      })
      ->pluck('id');

      // ->map(function ($item) use (&$recorded) {
      //   foreach ([$item->homeUserLineupMeta, $item->awayUserLineupMeta] as $userLineupMeta) {
      //     // dd($userLineupMetaId);
      //     SimulationSchedule::where([
      //       'league_id' => $item->league_id,
      //       'status' => SimulationScheduleStatus::PLAYED,
      //     ])
      //     ->where(function ($query) use ($userLineupMeta) {
      //       $query->where('home_user_lineup_meta_id', $userLineupMeta->id)
      //         ->orWhere('away_user_lineup_meta_id', $userLineupMeta->id);
      //     })
      //       ->selectRaw("count(id) as count_played,
      //           CAST(SUM(CASE WHEN (home_user_lineup_meta_id = $userLineupMeta->id AND winner = 'home')
      //             OR (away_user_lineup_meta_id = $userLineupMeta->id AND winner = 'away') THEN 1 ELSE 0 END) AS unsigned) AS count_won,
      //           CAST(SUM(CASE WHEN (home_user_lineup_meta_id = $userLineupMeta->id AND winner = 'away')
      //             OR (away_user_lineup_meta_id = $userLineupMeta->id AND winner = 'home') THEN 1 ELSE 0 END) AS unsigned) AS count_lost,
      //           CAST(SUM(CASE WHEN home_user_lineup_meta_id = $userLineupMeta->id AND winner = 'draw' THEN 1 ELSE 0 END) AS unsigned) AS count_draw
      //       ")
      //     ->get();
      //
      //     SimulationUserRank::updateOrCreateEx([
      //       'applicant_id' => $userLineupMeta->applicant_id,
      //       'league_id' => $item->league_id,
      //       'last_started_at' => $lastStartedAt->clone()->setTimezone(config('app.timezone')),
      //       'week' => $firstStartedAt->week,
      //     ], []);
      //   }
      // });

    DB::beginTransaction();
    try {
      (new SimulationStatAggrUpdator($schedules->toArray()))->update();
      SimulationSchedule::whereIn('id', $schedules)
        ->update([
          'is_rank_completed' => true,
        ]);
      DB::commit();
    } catch (Throwable $th) {
      logger($th->getMessage());
      DB::rollBack();
    }
    return 0;
  }
}

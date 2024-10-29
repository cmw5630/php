<?php

namespace App\Console;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Models\data\Schedule as DataSchedule;
use App\Models\game\Game;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ShortSchedule\ShortSchedule;

class Kernel extends ConsoleKernel
{
  /**
   * Define the application's command schedule.
   *
   * @param Schedule $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule)
  {
    $schedule->command('passport:purge')->hourly();
    $schedule->command('OP:auction-complete')->everyMinute()->withoutOverlapping();

    $schedule->command('quest-update')->at('00:00');

    $schedule->command('ingame:wrapup')->everyMinute()->withoutOverlapping();
    $schedule->command('ingame:lockcheck')->everyMinute()->withoutOverlapping();

    $schedule->command("sync-group" . ' ' . FantasySyncGroupType::DAILY)->everySixHours()->withoutOverlapping()->runInBackground(); // fantasy_metas(table)
    $schedule->command("sync-group" . ' ' . FantasySyncGroupType::ETC)->cron('0 3,9,15,21 * * *')->withoutOverlapping()->runInBackground(); // fantasy_metas(table)
    $schedule->command("sync-group" . ' ' . FantasySyncGroupType::CONDITIONALLY)->cron('*/10 * * * *')->withoutOverlapping()->runInBackground()->when(
      function () {
        return Game::isEnded(false)->whereBetween('start_date', [now()->addMinutes(20), now()->addHours(2)])->exists();
      }
    ); // fantasy_metas(table)
    $schedule->command("sync-group" . ' ' . FantasySyncGroupType::ELASTIC)->everyTenMinutes()->withoutOverlapping()->runInBackground()->when(
      function () {
        return DataSchedule::whereBetween('started_at', [
          Carbon::now()->subHours(4),
          Carbon::now()->subHour(1),
        ])->exists();
      }
    );
    $schedule->command('telescope:prune --hours=48')->daily();

    $schedule->command('main-top-player')->dailyAt('00:00')->withoutOverlapping();

    // 시뮬레이션
    // KST 토요일 22시 => UTC 토요일 13시
    $schedule->command('SIM:make-user-rank --server=asia')->saturdays()->at('13:00')->withoutOverlapping();
    $schedule->command('SIM:make-user-rank')->saturdays()->at('22:00')->withoutOverlapping();
    // KST 일요일 5시 => UTC 토요일 20시
    $schedule->command('SIM:make-fixture --server=asia')->saturdays()->at('20:00')->withoutOverlapping();
    $schedule->command('SIM:make-fixture')->sundays()->at('05:00')->withoutOverlapping();
    $schedule->command('SIM:applicant-stat --server=asia')->everyMinute()->between('01:00', '14:00')->withoutOverlapping();
    $schedule->command('SIM:applicant-stat')->everyMinute()->between('10:00', '23:00')->withoutOverlapping();
    $schedule->command('SIM:update-season --server=asia')->sundays()->at('01:00')->withoutOverlapping();
    $schedule->command('SIM:update-season')->sundays()->at('10:00')->withoutOverlapping();
  }

  protected function shortSchedule(ShortSchedule $schedule)
  {
    $schedule->command('opta:dailystat live')->everySecond(config('constant.COLLECT_MA2_LIVE_DELAY_SECOND'))->between('00:00', '23:59')->withoutOverlapping();
    $schedule->command('opta:dailystat unreal')->everySecond(10)->between('00:00', '23:59')->withoutOverlapping();
    // simulation
    $schedule->command('simulation:make')->everySecond(30)->between('00:00', '23:59')->withoutOverlapping();
    $schedule->command('simulation:live')->everySecond(25)->between('00:00', '23:59')->withoutOverlapping();
    $schedule->command('simulation:statuscheck')->everySecond(30)->between('00:00', '23:59')->withoutOverlapping();
  }

  /**
   * Register the commands for the application.
   *
   * @return void
   */
  protected function commands()
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}

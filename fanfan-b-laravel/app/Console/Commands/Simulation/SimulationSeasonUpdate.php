<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Opta\YesNo;
use App\Models\simulation\SimulationSeason;
use Carbon\CarbonInterface;
use DB;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class SimulationSeasonUpdate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'SIM:update-season {--server=?}';

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

    DB::beginTransaction();
    try {
      $curSeason = SimulationSeason::currentSeasons()
        ->where('server', $server)
        ->first();
      $curSeason->active = YesNo::NO;
      $curSeason->save();

      $now = now(config('simulationpolicies')['server'][$server]['timezone']);
      $seasonStartDate = $now->next(CarbonInterface::MONDAY)->toDateString();
      $nextSeason = SimulationSeason::whereDate('first_started_at', $seasonStartDate)
        ->firstWhere('server', $server);

      if (is_null($nextSeason)) {
        throw new Exception('No Next Season');
      }

      $nextSeason->active = YesNo::YES;
      $nextSeason->save();

      DB::commit();
    } catch (Throwable $th) {
      logger($th->getMessage());
      DB::rollBack();
    }
    return 0;
  }
}

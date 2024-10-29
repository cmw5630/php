<?php

namespace App\Console\Commands\Simulation;

use App\Models\simulation\RefSimulationScenario;
use Illuminate\Console\Command;

class SimulationSequenceUpdate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'simulation:squpdate';

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
    RefSimulationScenario::with('refSimulationSequence')
      ->get()
      ->map(function ($item) {
        foreach (['first', 'second'] as $nth) {
          ${$nth} = array_filter($item->refSimulationSequence->toArray(), function ($innerItem) use ($nth) {
            return $innerItem['nth_half'] === $nth;
          });
          ${$nth . 'TickTime'} = (45 * 60) / (count(${$nth}) - 1);
        }
        $firstTotalTime = 0;
        $secondTotalTime = 0;
        $item->refSimulationSequence()->get()->map(function ($sqItem) use (
          $firstTickTime, // 지바
          $secondTickTime, // 지바
          &$firstTotalTime,
          &$secondTotalTime,
        ) {
          if (in_array($sqItem->ending, ['first_half_end', 'second_half_end'])) {
            $sqItem->playing_seconds = ${$sqItem->nth_half . 'TotalTime'};
          } else if ($sqItem->ending === 'second_half_start') {
            $sqItem->playing_seconds = ${$sqItem->nth_half . 'TotalTime'} = ${$sqItem->nth_half . 'TotalTime'} + 60 * 15;
          } else {
            $sqItem->playing_seconds = ${$sqItem->nth_half . 'TotalTime'} = ${$sqItem->nth_half . 'TotalTime'} + ${$sqItem->nth_half . 'TickTime'} + random_int(0, 10);
          }
          $sqItem->save();
          if ($sqItem->nth_half === 'first') $secondTotalTime = $firstTotalTime;
        });
      });

    return 0;
  }
}

<?php

namespace App\Console\Commands;

use App\Console\Commands\DataControll\Live\LiveMA2MatchStatsParser;
use App\Console\Commands\OptaParsers\MA2MatchStatsParser;
use Illuminate\Console\Command;

class DailyStat extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'opta:dailystat {mode?}';

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
    $mode = $this->argument('mode');
    $param = [];
    if (!empty($mode)) {
      if ($mode === 'live') {
        (new LiveMA2MatchStatsParser())->start(true);
        return 0;
      } else if ($mode === 'unreal') {
        (new LiveMA2MatchStatsParser())->unrealParse(true);
        return 0;
      } else if ($mode === 'log') {
        (new LiveMA2MatchStatsParser())->startCollectLiveLog();
        return 0;
      }
      $param = [
        'mode' => $mode
      ];
    }
    (new MA2MatchStatsParser())->setParams($param)->start(true);
    return 0;
  }
}

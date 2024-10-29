<?php

namespace App\Console\Commands;

use App\Console\Commands\DataControll\Live\LiveMA2MatchStatsParser;
use App\Console\Commands\DataControll\Live\LiveWrapupDraft;
use App\Console\Commands\OptaParsers\MA2MatchStatsParser;
use Illuminate\Console\Command;

class LiveWraup extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ingame:wrapup';

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
    (new LiveWrapupDraft())->start(true);
    return 0;
  }
}

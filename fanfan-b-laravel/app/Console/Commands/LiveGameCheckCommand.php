<?php

namespace App\Console\Commands;

use App\Console\Commands\DataControll\Live\LiveGameChecker;
use App\Enums\System\NotifyLevel;
use DB;
use Illuminate\Console\Command;

class LiveGameCheckCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ingame:lockcheck';

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
    (new LiveGameChecker)->start();
  }
}

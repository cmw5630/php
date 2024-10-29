<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateFreshCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'migrate:fresh';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = '[Disabled] Drop all tables and re-run all migrations';

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
    $this->warn("It's inadvisable to run migrate:fresh on this project!");
    return 0;
  }
}

<?php

namespace App\Jobs;

use App\Libraries\Traits\DraftTrait;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobCompleteDraft implements ShouldQueue
{
  use DraftTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected array $datas;

  /**
   * Create a new job instance.
   *
   * @return void
   */

  public $timeout = 300;

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
    $this->finishDraft($this->datas);
  }
}

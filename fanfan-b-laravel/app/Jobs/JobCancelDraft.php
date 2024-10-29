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

class JobCancelDraft implements ShouldQueue
{
  use DraftTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected string $scheduleId;
  protected string $realStatus;

  /**
   * Create a new job instance.
   *
   * @return void
   */

  public $timeout = 300;

  public function __construct(string $_scheduleId, string|null $_realStatus = null)
  {
    $this->scheduleId = $_scheduleId;
    $this->realStatus = $_realStatus;
    $this->onQueue('low')->afterCommit();
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $this->cancelDraftAllByScheduleId($this->scheduleId, $this->realStatus);
  }
}

<?php

namespace App\Jobs;

use App\Events\DeprecatedLiveUserFpTotalPublishEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DepreactedJobUpdateGameJoinRanking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $scheduleId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($_scheduleId)
    {
        $this->scheduleId = $_scheduleId;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // game_joins 계산 후 broadcast


        DeprecatedLiveUserFpTotalPublishEvent::broadcast($this->data);
    }
}

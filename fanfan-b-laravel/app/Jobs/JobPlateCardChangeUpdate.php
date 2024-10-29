<?php

namespace App\Jobs;

use App\Console\Commands\DataControll\PlateCardPriceChangeUpdator;
use App\Console\Commands\DataControll\PlateCardRankUpdator;
use App\Console\Commands\DataControll\PlayerOverallUpdator;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JobPlateCardChangeUpdate implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


  /**
   * The number of seconds the job can run before timing out.
   *
   * @var int
   */
  public $timeout = 600;

  protected $targetPlayers;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(array $_targetPlayers = [])
  {
    $this->targetPlayers = $_targetPlayers;
    $this->onConnection('redis')->beforeCommit();
    //
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    logger('가격변동 / 오버롤 로직 시작');
    if (empty($this->targetPlayers)) {
      (new PlateCardPriceChangeUpdator())->update();
      // logger('가격 변동 로직 성공');
      (new PlayerOverallUpdator())->update();
      (new PlateCardRankUpdator())->update();
    } else {
      foreach ($this->targetPlayers as $playerId) {
        (new PlateCardPriceChangeUpdator($playerId))->update();
        (new PlayerOverallUpdator($playerId))->update();
        (new PlateCardRankUpdator($playerId))->update();
      }
    }
    logger('가격 변동 / 오버롤 로직 성공');
  }
}

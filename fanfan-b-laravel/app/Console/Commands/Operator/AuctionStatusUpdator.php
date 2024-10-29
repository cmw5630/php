<?php

namespace App\Console\Commands\Operator;

use App\Enums\AuctionStatus;
use App\Libraries\Classes\Exception;
use App\Models\game\Auction;
use App\Services\Market\MarketService;
use DB;
use Illuminate\Console\Command;
use Throwable;

class AuctionStatusUpdator extends Command
{
  protected MarketService $auctionService;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'OP:auction-complete';

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
  public function __construct(MarketService $_auctionService)
  {
    parent::__construct();
    $this->auctionService = $_auctionService;
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    // api db와 log db 간 lock이 걸려 업데이트가 되지 않은 현상이 있을 수 있음.
    logger('Auction Complete Scheduler Start');
    DB::beginTransaction();
    try {
      $auctions = Auction::where([
        ['expired_at', '<=', now()],
        ['status', AuctionStatus::BIDDING]
      ])->with('userPlateCard')
        ->lockForUpdate()
        ->get();

      if ($auctions->count() < 1) {
        throw new Exception('Nothing to complete Auction');
      }

      foreach ($auctions as $auction) {
        $this->auctionService->highestBidProcess($auction);
      }
      DB::commit();
      logger(sprintf('Auction Complete : %s', implode(',', $auctions->pluck('id')->toArray())));
    } catch (Throwable $th) {
      DB::rollback();
      logger($th->getMessage());
    }
    logger('Auction Complete Scheduler Finish');
    return 0;
  }
}

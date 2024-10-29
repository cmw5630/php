<?php

namespace App\Console\Commands;

use App\Console\Commands\DataControll\PlateCardPriceChangeUpdator;
use App\Models\game\PlateCard;
use App\Models\log\PlateCardPriceChangeLog;
use Illuminate\Console\Command;

class RecalculatePlateCardPriceChange extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'platecard {--type=} {--mode=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'plate card';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }


  public function resetCurrentSeasonPriceChange()
  {
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $param = [
      'type' => $this->option()['type'] ?? null, // pricechange/
      'mode' => $this->option()['mode'] ?? null, // reset,
    ];

    $type = $param['type'];
    $mode = $param['mode'];

    if ($type === null) {
      dd('--type 옵션 필요, --type=platecard {--mode=reset');
    }

    switch ($type) {
      case 'pricechange':
        if ($mode === 'reset') {
          PlateCard::isPriceSet()
            ->hasPowerRankingQuantile()
            ->get()
            ->map(function ($item) {
              $plateCardId = $item['id'];
              $currentSeasonId = $item['season_id'];
              PlateCardPriceChangeLog::where([
                ['plate_card_id', $plateCardId],
                ['season_id', $currentSeasonId],
              ])->forceDelete();
            });
        } else if ($mode === null) {
        } else {
          dd('do nothing($mode 옵션이 정확하지 않음.)');
          return;
        }
        (new PlateCardPriceChangeUpdator())->update();
        break;
      default:
        # code...
        break;
    }


    return 0;
  }
}

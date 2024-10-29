<?php

namespace App\Console\Commands;

use App\Models\log\PlateCardDailyAction;
use App\Libraries\Traits\CommonTrait;
use App\Services\Data\StatService;
use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Redis;

class MakePlateCardDailyTop extends Command
{
  use CommonTrait;
  private StatService $statService;

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'main-top-players';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command main-top-players';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct(StatService $statService)
  {
    parent::__construct();
    $this->statService = $statService;
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $plateCardDailyTop = [];
    $sub = PlateCardDailyAction::query()
      ->whereHas('plateCard', function ($query) {
        $query->isOnSale()->whereNotNull('headshot_path');
      })
      ->selectRaw('rank() OVER(PARTITION BY position ORDER BY (stats_count + plate_order_count) DESC, upgrade_count DESC, lineup_count DESC) AS rnum,
              player_id, season_id, position, stats_count, plate_order_count, upgrade_count, lineup_count')
      ->whereBetween('based_at', [now()->subDay()->startOfMonth()->toDateString(), now()->subDay()->toDateString()]);

    DB::query()
      ->select('player_id', 'season_id', 'position', 'stats_count', 'plate_order_count', 'upgrade_count', 'lineup_count')
      ->fromSub($sub, 'sub')
      ->where('rnum', 1)
      ->get()
      ->map(function ($item) use (&$plateCardDailyTop) {
        $seasonStat = $this->statService->getSeasonStatSummary($item->player_id);

        $plateCardDailyTop[] = [
          'player_id' => $item->player_id,
          'season_id' => $item->season_id,
          'position' => $item->position,
          'matches' => $seasonStat['stat']['matches'],
          'goals' => $seasonStat['stat']['goals'],
          'ratings' => $seasonStat['stat']['ratings'],
          'assists' => $seasonStat['stat']['assists'],
          'saves' => $seasonStat['stat']['saves'],
          'clean_sheets' => $seasonStat['stat']['clean_sheets']
        ];
      });

    Redis::set($this->getRedisCachingKey('plate_card_daily_tops','', now()->subDay()->format('Y-m-d')), json_encode($plateCardDailyTop), 'EX', 86400);

    return 0;
  }
}

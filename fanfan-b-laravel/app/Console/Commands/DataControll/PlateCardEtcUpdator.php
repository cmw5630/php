<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\YesNo;
use App\Enums\System\NotifyLevel;
use App\Models\data\Squad;
use App\Models\game\PlateCard;
use DB;
use Illuminate\Console\Command;
use Throwable;

class PlateCardEtcUpdator extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected  $signature = 'plate-card-etc-update';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command plate-card-shirt-number-update';

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
    try {
      DB::beginTransaction();
      logger('Update plate card etc start');
      __telegramNotify(NotifyLevel::INFO, 'Update plate card etc', 'Update plate card etc start');
      $squads = Squad::withTrashed()
        ->where('active', YesNo::YES)
        ->select('player_id as player', 'league_id as league', 'season_id as season', 'team_id as team', 'shirt_number as shirt_num')
        ->groupBy('player', 'league', 'season', 'team', 'shirt_num');

      $updateCardCount = PlateCard::withTrashed()
        ->withoutGlobalScopes()
        ->select('player_id', 'league_id', 'season_id', 'team_id', 'shirt_number',
          'player', 'league', 'season', 'team', 'shirt_num')
        ->joinSub($squads, 'squads', function ($join) {
          $plateCardTable = PlateCard::getModel()->getTable();
          $join->on($plateCardTable . '.player_id', 'squads.player');
          $join->on($plateCardTable . '.league_id', 'squads.league');
          $join->on($plateCardTable . '.season_id', 'squads.season');
          $join->on($plateCardTable . '.team_id', 'squads.team');
        })
        ->update(['shirt_number' => DB::raw('`shirt_num`')]);

      logger('Update plate card etc count: ' . $updateCardCount);
      __telegramNotify(NotifyLevel::INFO, 'Update plate card etc', 'Update plate card etc count: ' . $updateCardCount);
      DB::commit();
    } catch (Throwable $e) {
      logger('Update plate card etc fail (RollBack)');
      logger($e);
      DB::rollBack();
    }
  }
}

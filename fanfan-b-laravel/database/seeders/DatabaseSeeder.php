<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use Throwable;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    // DB::beginTransaction();

    try {
      $this->call([
        FantasyMetaSeeder::class,
        OauthClientSeeder::class,
        UserSeeder::class,
        FantasyGradePriceSeeder::class,
        RefPointCQuantilesSeeder::class,
        RefCardCQuantilesSeeder::class,
        RefTransfersSeeder::class,
        LeagueTierSeeder::class,
        RefDraftPricesSeeder::class,
        RefTeamTierBonusesSeeder::class,
        RefPriceGradeTransformMapsSeeder::class,
        RefPowerRankingQuantilesSeeder::class,
        PValidScheduleStageSeeder::class,
        RefPlateCQuantileSeeder::class,
        RefMarketMinimumPriceSeeder::class,
        QuestSeeder::class,
        BoardCategorySeeder::class,

        // Admin
        AdminRoleSeeder::class,
        AdminPermissionSeeder::class,
        AdminRoleSyncPermissionSeeder::class,
        AdminSeeder::class,
        RefCountryCodeSeeder::class,
      ]);

      // DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }
}

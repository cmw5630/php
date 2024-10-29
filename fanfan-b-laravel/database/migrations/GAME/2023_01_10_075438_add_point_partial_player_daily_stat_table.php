<?php

use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('player_daily_stats', function (Blueprint $table) {
      $table->float(FantasyPointCategoryType::GOALKEEPING . '_point')->default(0)->after('fantasy_point')->comment('goalkeeping');
      $table->float(FantasyPointCategoryType::DUEL . '_point')->default(0)->after('fantasy_point')->comment('duel');
      $table->float(FantasyPointCategoryType::PASSING . '_point')->default(0)->after('fantasy_point')->comment('passing');
      $table->float(FantasyPointCategoryType::DEFENSIVE . '_point')->default(0)->after('fantasy_point')->comment('defensive');
      $table->float(FantasyPointCategoryType::OFFENSIVE . '_point')->default(0)->after('fantasy_point')->comment('attacking');
      $table->float(FantasyPointCategoryType::GENERAL . '_point')->default(0)->after('fantasy_point')->comment('general');
    });
    //
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('player_daily_stats', function (Blueprint $table) {
      $table->dropColumn(FantasyPointCategoryType::OFFENSIVE . '_point');
      $table->dropColumn(FantasyPointCategoryType::DEFENSIVE . '_point');
      $table->dropColumn(FantasyPointCategoryType::PASSING . '_point');
      $table->dropColumn(FantasyPointCategoryType::DUEL . '_point');
      $table->dropColumn(FantasyPointCategoryType::GOALKEEPING . '_point');
    });
    //
  }
};

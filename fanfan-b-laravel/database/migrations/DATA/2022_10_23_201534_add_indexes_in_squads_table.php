<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::connection('data')->table('squads', function (Blueprint $table) {
      $table->index([
        'season_id', 'team_id', 'player_id'
      ]);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('data')->table('squads', function (Blueprint $table) {
      $table->dropIndex([
        'season_id', 'team_id', 'player_id'
      ]);
    });
  }
};

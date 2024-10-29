<?php

use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_player_base_projections', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('player_id');
      $table->enum('team_side', [ScheduleWinnerStatus::HOME, ScheduleWinnerStatus::AWAY]);
      $table->float('fantasy_point_total');
      $table->float('mins_played_total');
      $table->float('game_started_total');
      $table->float('total_sub_on_total');
      $table->float('fp_per_min');
      $table->float('avg_played_time');
      $table->float('base_value');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_player_base_projections');
  }
};

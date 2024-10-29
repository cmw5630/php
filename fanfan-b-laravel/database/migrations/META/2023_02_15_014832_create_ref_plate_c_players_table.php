<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;
use Illuminate\Database\Query\Expression;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_plate_c_players', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->comment('player id')->constrained();
      $table->foreignUuid('league_id')->comment('리그 id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('source_season_id')->comment('source season id 포함 최대 이전 3 season이 계산에 포함된')->constrained(Season::getModel()->getTable());
      $table->float('plate_c')->comment('plate_c');
      $table->float('power_ranking_total')->comment('sum(power_ranking)');
      $table->float('game_started_total')->comment('선발 출장 총 합');
      $table->float('total_sub_on_total')->comment('교체 출장 총 합');
      $table->float('entry_total')->comment('엔트리에 든 경우 총 합');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('ref_plate_c_players');
    Schema::enableForeignKeyConstraints();
  }
};

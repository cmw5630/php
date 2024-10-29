<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_team_tier_bonuses', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->string('league_name')->comment('리그 이름');
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->char('season_name', 10)->comment('시즌 년도 이름');
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
      $table->string('team_name');
      $table->smallInteger('rank');
      $table->decimal('normalized_bonus', 8, 3);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_team_tier_bonuses');
  }
};

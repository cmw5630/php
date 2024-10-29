<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('team_change_histories', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable());
      // $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      // $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('before_team_id')->constrained(Team::getModel()->getTable());
      $table->foreignUuid('current_team_id')->nullable()->constrained(Team::getModel()->getTable());
      $table->boolean('is_treated')->default(false);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('team_change_histories');
  }
};

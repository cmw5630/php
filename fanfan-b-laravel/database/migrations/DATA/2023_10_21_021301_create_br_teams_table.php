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
    Schema::connection('data')->create('br_teams', function (Blueprint $table) {
      $table->id();
      $table->string('name', 100);
      $table->string('short_name', 100);
      $table->string('abbreviation', 16);
      $table->unsignedBigInteger('br_team_id');
      $table->foreignUuid('opta_league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('opta_season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('opta_team_id')->nullable()->constrained(Team::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('br_teams');
  }
};

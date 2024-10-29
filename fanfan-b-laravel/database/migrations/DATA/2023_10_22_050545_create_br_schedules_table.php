<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('br_schedules', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('sport_event_id');
      $table->foreignUuid('opta_league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('opta_season_id')->constrained(Season::getModel()->getTable());
      $table->unsignedBigInteger('br_home_team_id');
      $table->unsignedBigInteger('br_away_team_id');
      $table->timestamp('start_time');
      $table->unsignedSmallInteger('round')->nullable();
      $table->foreignUuid('opta_schedule_id')->nullable()->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('opta_home_team_id')->nullable()->constrained(Team::getModel()->getTable());
      $table->foreignUuid('opta_away_team_id')->nullable()->constrained(Team::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('br_schedules');
  }
};

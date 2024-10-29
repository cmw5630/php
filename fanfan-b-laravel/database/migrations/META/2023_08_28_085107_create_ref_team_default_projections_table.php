<?php

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
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
    Schema::create('ref_team_default_projections', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
      $table->enum('team_side', [ScheduleWinnerStatus::HOME, ScheduleWinnerStatus::AWAY])->constrained(Team::getModel()->getTable());
      $table->float(Str::lower(PlayerPosition::ATTACKER), 5, 3);
      $table->float(Str::lower(PlayerPosition::MIDFIELDER), 5, 3);
      $table->float(Str::lower(PlayerPosition::DEFENDER), 5, 3);
      $table->float(Str::lower(PlayerPosition::GOALKEEPER), 5, 3);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_team_default_projections');
  }
};

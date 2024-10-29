<?php

use App\Enums\Opta\Card\PowerGrade;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\game\PlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_player_pools', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('player_id')->constrained(PlateCard::getModel()->getTable(), 'player_id');
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
      $table->enum('position', PlayerPosition::getValues());
      $table->unsignedInteger('price');
      $table->float('avg_pw');
      $table->unsignedInteger('nrank');
      $table->enum('power_grade', PowerGrade::getValues());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_player_pools');
  }
};

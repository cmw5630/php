<?php

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('injuries', function (Blueprint $table) {
      $table->id();
      $table->string('league_name');
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->string('season_name');
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->timestamp('last_updated');
      $table->timestamp('injury_start_date');
      $table->string('injury_type');
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable());
      $table->enum('person_type', PlayerType::getValues());
      $table->enum('person_position', PlayerPosition::getValues());
      $table->timestamp('injury_end_date')->nullable();
      $table->timestamp('expected_end_date')->nullable();
      $table->string('known_name')->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('injuries');
  }
};

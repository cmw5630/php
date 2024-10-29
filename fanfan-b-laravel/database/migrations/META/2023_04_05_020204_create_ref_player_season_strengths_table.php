<?php

use App\Enums\PlayerStrengthType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_player_season_strengths', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->comment('플레이어 id')->constrained();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());

      $table->enum('finishing', PlayerStrengthType::getValues())->nullable();
      $table->enum('dribbling', PlayerStrengthType::getValues())->nullable();
      $table->enum('shooting', PlayerStrengthType::getValues())->nullable();
      $table->enum('assists', PlayerStrengthType::getValues())->nullable();
      // $table->enum('passing', PlayerStrengthType::getValues())->nullable();
      $table->enum('key_passes', PlayerStrengthType::getValues())->nullable();
      $table->enum('crossing', PlayerStrengthType::getValues())->nullable();
      $table->enum('long_passes', PlayerStrengthType::getValues())->nullable();
      $table->enum('free_kicks', PlayerStrengthType::getValues())->nullable();
      $table->enum('clearances', PlayerStrengthType::getValues())->nullable();
      $table->enum('tackling', PlayerStrengthType::getValues())->nullable();
      // $table->enum('duels_won', PlayerStrengthType::getValues())->nullable();
      $table->enum('aerial_duels', PlayerStrengthType::getValues())->nullable();
      $table->enum('interception', PlayerStrengthType::getValues())->nullable();
      $table->enum('clean_sheet', PlayerStrengthType::getValues())->nullable();
      $table->enum('saving', PlayerStrengthType::getValues())->nullable();
      $table->enum('high_claim', PlayerStrengthType::getValues())->nullable();
      $table->enum('saved_in_box', PlayerStrengthType::getValues())->nullable();

      $table->timestamps();

      $table->unique(['player_id', 'season_id']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_player_season_strengths');
  }
};

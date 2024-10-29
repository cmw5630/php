<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('draft_completes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->foreignId('user_plate_card_id')->unique()->comment('사용자 카드 ID')->constrained();
      // attacking
      $table->enum('summary_position', PlayerPosition::getValues());
      $table->smallInteger('assists')->default(0);
      $table->smallInteger('winning_goal')->default(0);
      $table->smallInteger('shots_on_target')->default(0);
      $table->smallInteger('successful_dribbles')->default(0);
      $table->smallInteger('goals')->default(0);
      // passing
      $table->smallInteger('accurate_crosses')->default(0);
      $table->smallInteger('passes_into_final_third')->default(0);
      $table->smallInteger('key_passes')->default(0);
      $table->smallInteger('accurate_long_passes')->default(0);
      $table->smallInteger('pass_accuracy')->default(0);
      // defensive
      $table->smallInteger('offside_provoked')->default(0);
      $table->smallInteger('clean_sheet')->default(0);
      $table->smallInteger('tackles_won')->default(0);
      $table->smallInteger('blocks')->default(0);
      $table->smallInteger('clearances')->default(0);
      // duels
      $table->smallInteger('aerial_duel_won')->default(0);
      $table->smallInteger('ground_duels_won')->default(0);
      $table->smallInteger('recoveries')->default(0);
      $table->smallInteger('interceptions')->default(0);
      $table->smallInteger('duels_won')->default(0);
      // goalkeeping
      $table->smallInteger('acted_as_sweeper')->default(0);
      $table->smallInteger('saved_in_box')->default(0);
      $table->smallInteger('punches')->default(0);
      $table->smallInteger('high_claims')->default(0);
      $table->smallInteger('saves')->default(0);

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('draft_completes');
    Schema::enableForeignKeyConstraints();
  }
};

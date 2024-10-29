<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('draft_selection_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id');
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 ID');
      $table->foreignUuid('league_id')->comment('리그 ID')->constrained(League::getModel()->getTable(), 'id');
      $table->foreignUuid('team_id')->comment('team ID')->constrained(Team::getModel()->getTable(), 'id');
      $table->foreignUuid('schedule_id')->comment('스케쥴 ID')->constrained(Schedule::getModel()->getTable(), 'id');
      $table->timestamp('schedule_started_at')->comment('스케쥴 시작 시간');
      $table->foreignUuid('player_id')->comment('player id');

      // log 화면 필요 column
      $table->string('user_name')->comment('사용자 닉네임');
      $table->smallInteger('selection_level')->comment('선택한 강화 수치');
      $table->smallInteger('selection_cost')->comment('선택한 강화 코스트');
      $table->integer('selection_point')->comment('선택한 강화 포인트');

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
    Schema::connection('log')->dropIfExists('draft_selection_logs');
  }
};

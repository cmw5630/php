<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('opta_schedule_season_rankings', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment()->constrained();
      $table->foreignUuid('season_id')->comment()->constrained();
      $table->foreignUuid('schedule_id')->comment()->constrained();
      $table->foreignUuid('home_team_id')->comment()->constrained(Team::getModel()->getTable());
      $table->foreignUuid('away_team_id')->comment()->constrained(Team::getModel()->getTable());

      $table->smallInteger('total_accurate_pass')->default(0)->comment();
      $table->smallInteger('total_accurate_pass_ranking')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_ibox')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_ibox_ranking')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_obox')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_obox_ranking')->default(0)->comment();
      $table->smallInteger('total_fouls')->default(0)->comment();
      $table->smallInteger('total_fouls_ranking')->default(0)->comment();
      $table->smallInteger('total_goals')->default(0)->comment();
      $table->smallInteger('total_goals_conceded')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ibox')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ibox_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_obox')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_obox_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_ranking')->default(0)->comment();
      $table->smallInteger('total_pass')->default(0)->comment();
      $table->smallInteger('total_pass_ranking')->default(0)->comment();
      $table->smallInteger('total_red_card')->default(0)->comment();
      $table->smallInteger('total_red_card_ranking')->default(0)->comment();
      $table->smallInteger('total_scoring_att')->default(0)->comment();
      $table->smallInteger('total_scoring_att_ranking')->default(0)->comment();
      $table->smallInteger('total_tackle')->default(0)->comment();
      $table->smallInteger('total_tackle_ranking')->default(0)->comment();
      $table->smallInteger('total_was_fouled')->default(0)->comment();
      $table->smallInteger('total_was_fouled_ranking')->default(0)->comment();
      $table->smallInteger('total_won_tackle')->default(0)->comment();
      $table->smallInteger('total_won_tackle_ranking')->default(0)->comment();
      $table->smallInteger('total_yellow_card')->default(0)->comment();
      $table->smallInteger('total_yellow_card_ranking')->default(0)->comment();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('opta_schedule_season_rankings');
  }
};

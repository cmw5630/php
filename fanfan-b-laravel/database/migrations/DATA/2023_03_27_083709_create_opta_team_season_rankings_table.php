<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('opta_team_season_rankings', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('')->constrained();
      $table->foreignUuid('season_id')->comment('')->constrained();
      $table->foreignUuid('team_id')->comment('')->constrained();

      $table->smallInteger('total_accurate_cross')->default(0)->comment();
      $table->smallInteger('total_accurate_cross_ranking')->default(0)->comment();
      $table->smallInteger('total_accurate_pass')->default(0)->comment();
      $table->smallInteger('total_accurate_pass_ranking')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_ibox')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_ibox_ranking')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_obox')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_obox_ranking')->default(0)->comment();
      $table->smallInteger('total_attempts_conceded_ranking')->default(0)->comment();
      $table->smallInteger('total_blocked_scoring_att')->default(0)->comment();
      $table->smallInteger('total_blocked_scoring_att_ranking')->default(0)->comment();
      $table->smallInteger('total_card')->default(0)->comment();
      $table->smallInteger('total_card_ranking')->default(0)->comment();
      $table->smallInteger('total_claim')->default(0)->comment();
      $table->smallInteger('total_claim_ranking')->default(0)->comment();
      $table->smallInteger('total_clean_sheet')->default(0)->comment();
      $table->smallInteger('total_clean_sheet_ranking')->default(0)->comment();
      $table->smallInteger('total_clearance')->default(0)->comment();
      $table->smallInteger('total_clearance_ranking')->default(0)->comment();
      $table->smallInteger('total_contest')->default(0)->comment();
      $table->smallInteger('total_contest_ranking')->default(0)->comment();
      $table->smallInteger('total_cross')->default(0)->comment();
      $table->smallInteger('total_cross_pct')->default(0)->comment();
      $table->smallInteger('total_cross_pct_ranking')->default(0)->comment();
      $table->smallInteger('total_cross_ranking')->default(0)->comment();
      $table->smallInteger('total_duels_lost')->default(0)->comment();
      $table->smallInteger('total_duels_lost_ranking')->default(0)->comment();
      $table->smallInteger('total_duels_won')->default(0)->comment();
      $table->smallInteger('total_duels_won_ranking')->default(0)->comment();
      $table->smallInteger('total_fouls')->default(0)->comment();
      $table->smallInteger('total_fouls_ranking')->default(0)->comment();
      $table->smallInteger('total_games')->default(0)->comment();
      $table->smallInteger('total_games_ranking')->default(0)->comment();
      $table->smallInteger('total_goal_conversion')->default(0)->comment();
      $table->smallInteger('total_goal_conversion_ranking')->default(0)->comment();
      $table->smallInteger('total_goals')->default(0)->comment();
      $table->smallInteger('total_goals_conceded')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ibox')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ibox_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_obox')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_obox_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_conceded_ranking')->default(0)->comment();
      $table->smallInteger('total_goals_ranking')->default(0)->comment();
      $table->smallInteger('total_lost_corners')->default(0)->comment();
      $table->smallInteger('total_lost_corners_ranking')->default(0)->comment();
      $table->smallInteger('total_offside')->default(0)->comment();
      $table->smallInteger('total_offside_ranking')->default(0)->comment();
      $table->smallInteger('total_ontarget_scoring_att')->default(0)->comment();
      $table->smallInteger('total_ontarget_scoring_att_ranking')->default(0)->comment();
      $table->smallInteger('total_pass')->default(0)->comment();
      $table->smallInteger('total_pass_pct')->default(0)->comment();
      $table->smallInteger('total_pass_pct_ranking')->default(0)->comment();
      $table->smallInteger('total_pass_ranking')->default(0)->comment();
      $table->smallInteger('total_red_card')->default(0)->comment();
      $table->smallInteger('total_red_card_ranking')->default(0)->comment();
      $table->smallInteger('total_scoring_accuracy')->default(0)->comment();
      $table->smallInteger('total_scoring_accuracy_ranking')->default(0)->comment();
      $table->smallInteger('total_scoring_att')->default(0)->comment();
      $table->smallInteger('total_scoring_att_ranking')->default(0)->comment();
      $table->smallInteger('total_tackle')->default(0)->comment();
      $table->smallInteger('total_tackle_pct')->default(0)->comment();
      $table->smallInteger('total_tackle_pct_ranking')->default(0)->comment();
      $table->smallInteger('total_tackle_ranking')->default(0)->comment();
      $table->smallInteger('total_takeon')->default(0)->comment();
      $table->smallInteger('total_takeon_ranking')->default(0)->comment();
      $table->smallInteger('total_touches_in_opposition_box')->default(0)->comment();
      $table->smallInteger('total_touches_in_opposition_box_ranking')->default(0)->comment();
      $table->smallInteger('total_was_fouled')->default(0)->comment();
      $table->smallInteger('total_was_fouled_ranking')->default(0)->comment();
      $table->smallInteger('total_won_corners')->default(0)->comment();
      $table->smallInteger('total_won_corners_ranking')->default(0)->comment();
      $table->smallInteger('total_won_tackle')->default(0)->comment();
      $table->smallInteger('total_won_tackle_ranking')->default(0)->comment();
      $table->smallInteger('total_yellow_card')->default(0)->comment();
      $table->smallInteger('total_yellow_card_ranking')->default(0)->comment();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('opta_team_season_rankings');
  }
};

<?php

use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use Illuminate\Database\Query\Expression;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('opta_player_daily_stats', function (Blueprint $table) {
      $api = config('database.connections.api.database');
      $table->id();
      $table->foreignUuid('schedule_id')->constrained();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->foreignUuid('player_id')->constrained(new Expression($api . '.players'));

      $table->float('rating')->default(0);
      $table->float('fantasy_point')->default(0);
      $table->boolean('is_mom')->default(false)->comment('MOM 여부');
      $table->float('power_ranking')->default(0)->comment('파워 랭킹');

      $table->enum('position', PlayerDailyPosition::getValues())->nullable();
      $table->enum('sub_position', PlayerPosition::getValues())->nullable();
      $table->enum('summary_position', PlayerPosition::getValues());

      $table->enum('status', ScheduleStatus::getValues());
      $table->smallInteger('accurate_back_zone_pass')->default(0);
      $table->smallInteger('accurate_chipped_pass')->default(0);
      $table->smallInteger('accurate_corners_intobox')->default(0);
      $table->smallInteger('accurate_cross')->default(0);
      $table->smallInteger('accurate_cross_nocorner')->default(0);
      $table->smallInteger('accurate_flick_on')->default(0);
      $table->smallInteger('accurate_freekick_cross')->default(0);
      $table->smallInteger('accurate_fwd_zone_pass')->default(0);
      $table->smallInteger('accurate_goal_kicks')->default(0);
      $table->smallInteger('accurate_keeper_sweeper')->default(0);
      $table->smallInteger('accurate_keeper_throws')->default(0);
      $table->smallInteger('accurate_launches')->default(0);
      $table->smallInteger('accurate_layoffs')->default(0);
      $table->smallInteger('accurate_long_balls')->default(0);
      $table->smallInteger('accurate_pass')->default(0);
      $table->smallInteger('accurate_pull_back')->default(0);
      $table->smallInteger('accurate_through_ball')->default(0);
      $table->smallInteger('accurate_throws')->default(0);
      $table->smallInteger('aerial_lost')->default(0);
      $table->smallInteger('aerial_won')->default(0);
      $table->smallInteger('assist_attempt_saved')->default(0);
      $table->smallInteger('assist_blocked_shot')->default(0);
      $table->smallInteger('assist_free_kick_won')->default(0);
      $table->smallInteger('assist_handball_won')->default(0);
      $table->smallInteger('assist_own_goal')->default(0);
      $table->smallInteger('assist_pass_lost')->default(0);
      $table->smallInteger('assist_penalty_won')->default(0);
      $table->smallInteger('assist_post')->default(0);
      $table->smallInteger('att_assist_openplay')->default(0);
      $table->smallInteger('att_assist_setplay')->default(0);
      $table->smallInteger('att_bx_centre')->default(0);
      $table->smallInteger('att_bx_left')->default(0);
      $table->smallInteger('att_bx_right')->default(0);
      $table->smallInteger('att_cmiss_high')->default(0);
      $table->smallInteger('att_cmiss_high_left')->default(0);
      $table->smallInteger('att_cmiss_high_right')->default(0);
      $table->smallInteger('att_cmiss_left')->default(0);
      $table->smallInteger('att_cmiss_right')->default(0);
      $table->smallInteger('att_corner')->default(0);
      $table->smallInteger('att_fastbreak')->default(0);
      $table->smallInteger('att_freekick_goal')->default(0);
      $table->smallInteger('att_freekick_miss')->default(0);
      $table->smallInteger('att_freekick_post')->default(0);
      $table->smallInteger('att_freekick_target')->default(0);
      $table->smallInteger('att_freekick_total')->default(0);
      $table->smallInteger('att_goal_high_centre')->default(0);
      $table->smallInteger('att_goal_high_left')->default(0);
      $table->smallInteger('att_goal_high_right')->default(0);
      $table->smallInteger('att_goal_low_centre')->default(0);
      $table->smallInteger('att_goal_low_left')->default(0);
      $table->smallInteger('att_goal_low_right')->default(0);
      $table->smallInteger('att_hd_goal')->default(0);
      $table->smallInteger('att_hd_miss')->default(0);
      $table->smallInteger('att_hd_post')->default(0);
      $table->smallInteger('att_hd_target')->default(0);
      $table->smallInteger('att_hd_total')->default(0);
      $table->smallInteger('att_ibox_blocked')->default(0);
      $table->smallInteger('att_ibox_goal')->default(0);
      $table->smallInteger('att_ibox_miss')->default(0);
      $table->smallInteger('att_ibox_own_goal')->default(0);
      $table->smallInteger('att_ibox_post')->default(0);
      $table->smallInteger('att_ibox_target')->default(0);
      $table->smallInteger('att_lf_goal')->default(0);
      $table->smallInteger('att_lf_target')->default(0);
      $table->smallInteger('att_lf_total')->default(0);
      $table->smallInteger('att_lg_centre')->default(0);
      $table->smallInteger('att_lg_left')->default(0);
      $table->smallInteger('att_lg_right')->default(0);
      $table->smallInteger('att_miss_high')->default(0);
      $table->smallInteger('att_miss_high_left')->default(0);
      $table->smallInteger('att_miss_high_right')->default(0);
      $table->smallInteger('att_miss_left')->default(0);
      $table->smallInteger('att_miss_right')->default(0);
      $table->smallInteger('att_obox_blocked')->default(0);
      $table->smallInteger('att_obox_goal')->default(0);
      $table->smallInteger('att_obox_miss')->default(0);
      $table->smallInteger('att_obox_own_goal')->default(0);
      $table->smallInteger('att_obox_post')->default(0);
      $table->smallInteger('att_obox_target')->default(0);
      $table->smallInteger('att_obp_goal')->default(0);
      $table->smallInteger('att_obx_centre')->default(0);
      $table->smallInteger('att_obx_left')->default(0);
      $table->smallInteger('att_obx_right')->default(0);
      $table->smallInteger('att_obxd_left')->default(0);
      $table->smallInteger('att_obxd_right')->default(0);
      $table->smallInteger('att_one_on_one')->default(0);
      $table->smallInteger('att_openplay')->default(0);
      $table->smallInteger('att_pen_goal')->default(0);
      $table->smallInteger('att_pen_miss')->default(0);
      $table->smallInteger('att_pen_post')->default(0);
      $table->smallInteger('att_pen_target')->default(0);
      $table->smallInteger('att_post_high')->default(0);
      $table->smallInteger('att_post_left')->default(0);
      $table->smallInteger('att_post_right')->default(0);
      $table->smallInteger('att_rf_goal')->default(0);
      $table->smallInteger('att_rf_target')->default(0);
      $table->smallInteger('att_rf_total')->default(0);
      $table->smallInteger('att_setpiece')->default(0);
      $table->smallInteger('att_sv_high_centre')->default(0);
      $table->smallInteger('att_sv_high_left')->default(0);
      $table->smallInteger('att_sv_high_right')->default(0);
      $table->smallInteger('att_sv_low_centre')->default(0);
      $table->smallInteger('att_sv_low_left')->default(0);
      $table->smallInteger('att_sv_low_right')->default(0);
      $table->smallInteger('attempted_tackle_foul')->default(0);
      $table->smallInteger('attempts_conceded_ibox')->default(0);
      $table->smallInteger('attempts_conceded_obox')->default(0);
      $table->smallInteger('attempts_ibox')->default(0);
      $table->smallInteger('attempts_obox')->default(0);
      $table->smallInteger('back_pass')->default(0);
      $table->smallInteger('backward_pass')->default(0);
      $table->smallInteger('ball_recovery')->default(0);
      $table->smallInteger('big_chance_created')->default(0);
      $table->smallInteger('big_chance_missed')->default(0);
      $table->smallInteger('big_chance_scored')->default(0);
      $table->smallInteger('blocked_cross')->default(0);
      $table->smallInteger('blocked_pass')->default(0);
      $table->smallInteger('blocked_scoring_att')->default(0);
      $table->smallInteger('challenge_lost')->default(0);
      $table->smallInteger('clean_sheet')->default(0);
      $table->smallInteger('clearance_off_line')->default(0);
      $table->smallInteger('contentious_decision')->default(0);
      $table->smallInteger('corner_taken')->default(0);
      $table->smallInteger('cross_not_claimed')->default(0);
      $table->smallInteger('crosses18yard')->default(0);
      $table->smallInteger('crosses18yardplus')->default(0);
      $table->smallInteger('dangerous_play')->default(0);
      $table->smallInteger('dispossessed')->default(0);
      $table->smallInteger('dive_catch')->default(0);
      $table->smallInteger('dive_save')->default(0);
      $table->smallInteger('diving_save')->default(0);
      $table->smallInteger('duel_lost')->default(0);
      $table->smallInteger('duel_won')->default(0);
      $table->smallInteger('effective_blocked_cross')->default(0);
      $table->smallInteger('effective_clearance')->default(0);
      $table->smallInteger('effective_head_clearance')->default(0);
      $table->smallInteger('error_lead_to_goal')->default(0);
      $table->smallInteger('error_lead_to_shot')->default(0);
      $table->smallInteger('failed_to_block')->default(0);
      $table->smallInteger('fifty_fifty')->default(0);
      $table->smallInteger('final_third_entries')->default(0);
      $table->smallInteger('first_half_goals')->default(0);
      $table->smallInteger('formation_place')->default(0);
      $table->smallInteger('foul_lost')->default(0);
      $table->smallInteger('foul_throw_in')->default(0);
      $table->smallInteger('foul_won')->default(0);
      $table->smallInteger('fouled_final_third')->default(0);
      $table->smallInteger('fouls')->default(0);
      $table->smallInteger('freekick_cross')->default(0);
      $table->smallInteger('fwd_pass')->default(0);
      $table->boolean('game_started')->default(false);
      $table->smallInteger('gk_smother')->default(0);
      $table->smallInteger('goal_assist')->default(0);
      $table->smallInteger('goal_assist_deadball')->default(0);
      $table->smallInteger('goal_assist_intentional')->default(0);
      $table->smallInteger('goal_assist_openplay')->default(0);
      $table->smallInteger('goal_assist_setplay')->default(0);
      $table->smallInteger('goal_fastbreak')->default(0);
      $table->smallInteger('goal_kicks')->default(0);
      $table->smallInteger('goals')->default(0);
      $table->smallInteger('goals_conc_onfield')->default(0);
      $table->smallInteger('goals_conceded')->default(0);
      $table->smallInteger('goals_conceded_ibox')->default(0);
      $table->smallInteger('goals_conceded_obox')->default(0);
      $table->smallInteger('goals_openplay')->default(0);
      $table->smallInteger('good_high_claim')->default(0);
      $table->smallInteger('hand_ball')->default(0);
      $table->smallInteger('head_clearance')->default(0);
      $table->smallInteger('head_pass')->default(0);
      $table->smallInteger('hit_woodwork')->default(0);
      $table->smallInteger('interception')->default(0);
      $table->smallInteger('interception_won')->default(0);
      $table->smallInteger('interceptions_in_box')->default(0);
      $table->smallInteger('keeper_pick_up')->default(0);
      $table->smallInteger('keeper_throws')->default(0);
      $table->smallInteger('last_man_contest')->default(0);
      $table->smallInteger('last_man_tackle')->default(0);
      $table->smallInteger('leftside_pass')->default(0);
      $table->smallInteger('long_pass_own_to_opp')->default(0);
      $table->smallInteger('long_pass_own_to_opp_success')->default(0);
      $table->smallInteger('lost_corners')->default(0);
      $table->smallInteger('mins_played')->default(0);
      $table->smallInteger('offtarget_att_assist')->default(0);
      $table->smallInteger('offside_provoked')->default(0);
      $table->smallInteger('ontarget_att_assist')->default(0);
      $table->smallInteger('ontarget_scoring_att')->default(0);
      $table->smallInteger('open_play_pass')->default(0);
      $table->smallInteger('outfielder_block')->default(0);
      $table->smallInteger('overrun')->default(0);
      $table->smallInteger('own_goals')->default(0);
      $table->smallInteger('passes_left')->default(0);
      $table->smallInteger('passes_right')->default(0);
      $table->smallInteger('pen_area_entries')->default(0);
      $table->smallInteger('pen_goals_conceded')->default(0);
      $table->smallInteger('penalty_conceded')->default(0);
      $table->smallInteger('penalty_faced')->default(0);
      $table->smallInteger('penalty_save')->default(0);
      $table->smallInteger('penalty_won')->default(0);
      $table->smallInteger('poss_lost_all')->default(0);
      $table->smallInteger('poss_lost_ctrl')->default(0);
      $table->smallInteger('poss_won_att3rd')->default(0);
      $table->smallInteger('poss_won_def3rd')->default(0);
      $table->smallInteger('poss_won_mid3rd')->default(0);
      $table->smallInteger('post_scoring_att')->default(0);
      $table->smallInteger('punches')->default(0);
      $table->smallInteger('put_through')->default(0);
      $table->smallInteger('red_card')->default(0);
      $table->smallInteger('rescinded_red_card')->default(0);
      $table->smallInteger('rightside_pass')->default(0);
      $table->smallInteger('saved_ibox')->default(0);
      $table->smallInteger('saved_obox')->default(0);
      $table->smallInteger('saved_setpiece')->default(0);
      $table->smallInteger('saves')->default(0);
      $table->smallInteger('second_goal_assist')->default(0);
      $table->smallInteger('second_yellow')->default(0);
      $table->smallInteger('shield_ball_oop')->default(0);
      $table->smallInteger('shot_fastbreak')->default(0);
      $table->smallInteger('shot_off_target')->default(0);
      $table->smallInteger('shots_conc_onfield')->default(0);
      $table->smallInteger('six_second_violation')->default(0);
      $table->smallInteger('six_yard_block')->default(0);
      $table->smallInteger('stand_catch')->default(0);
      $table->smallInteger('stand_save')->default(0);
      $table->smallInteger('successful_fifty_fifty')->default(0);
      $table->smallInteger('successful_final_third_passes')->default(0);
      $table->smallInteger('successful_open_play_pass')->default(0);
      $table->smallInteger('successful_put_through')->default(0);
      $table->smallInteger('times_tackled')->default(0);
      $table->smallInteger('total_att_assist')->default(0);
      $table->smallInteger('total_attacking_pass')->default(0);
      $table->smallInteger('total_back_zone_pass')->default(0);
      $table->smallInteger('total_chipped_pass')->default(0);
      $table->smallInteger('total_clearance')->default(0);
      $table->smallInteger('total_contest')->default(0);
      $table->smallInteger('total_corners_intobox')->default(0);
      $table->smallInteger('total_cross')->default(0);
      $table->smallInteger('total_cross_nocorner')->default(0);
      $table->smallInteger('total_fastbreak')->default(0);
      $table->smallInteger('total_final_third_passes')->default(0);
      $table->smallInteger('total_flick_on')->default(0);
      $table->smallInteger('total_fwd_zone_pass')->default(0);
      $table->smallInteger('total_high_claim')->default(0);
      $table->smallInteger('total_keeper_sweeper')->default(0);
      $table->smallInteger('total_launches')->default(0);
      $table->smallInteger('total_layoffs')->default(0);
      $table->smallInteger('total_long_balls')->default(0);
      $table->smallInteger('total_offside')->default(0);
      $table->smallInteger('total_pass')->default(0);
      $table->smallInteger('total_pull_back')->default(0);
      $table->smallInteger('total_scoring_att')->default(0);
      $table->smallInteger('total_sub_off')->default(0);
      $table->smallInteger('total_sub_on')->default(0);
      $table->smallInteger('total_tackle')->default(0);
      $table->smallInteger('total_through_ball')->default(0);
      $table->smallInteger('total_throws')->default(0);
      // touches 예약어로 인한 변수명 변경
      $table->smallInteger('touches_opta')->default(0);
      $table->smallInteger('touches_in_opp_box')->default(0);
      $table->smallInteger('turnover')->default(0);
      $table->smallInteger('unknown')->default(0);
      $table->smallInteger('unsuccessful_touch')->default(0);
      $table->smallInteger('was_fouled')->default(0);
      $table->smallInteger('winning_goal')->default(0);
      $table->smallInteger('won_contest')->default(0);
      $table->smallInteger('won_corners')->default(0);
      $table->smallInteger('won_tackle')->default(0);
      $table->smallInteger('yellow_card')->default(0);
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();

      $table->index(['schedule_id', 'player_id']);
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('opta_player_daily_stats');
  }
};

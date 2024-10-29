<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('opta_team_season_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('team_id')->constrained();

      $table->float('rating', 5)->default(0.00);
      $table->smallInteger('rank')->default(0);
      $table->smallInteger('points')->default(0);
      $table->string('last_six', 6)->nullable();
      $table->string('rank_status', 200)->nullable();
      $table->smallInteger('matches_won')->nullable();
      $table->smallInteger('matches_lost')->nullable();
      $table->smallInteger('matches_drawn')->nullable();
      $table->smallInteger('goaldifference')->nullable();
      $table->smallInteger('aerial_duels')->default(0);
      $table->smallInteger('aerial_duelslost')->default(0);
      $table->smallInteger('aerial_duelswon')->default(0);
      $table->smallInteger('attemptsfrom_set_pieces')->default(0);
      $table->smallInteger('away_goals')->default(0);
      $table->smallInteger('blocked_shots')->default(0);
      $table->smallInteger('blocks')->default(0);
      $table->smallInteger('catches')->default(0);
      $table->smallInteger('clean_sheets')->default(0);
      $table->smallInteger('corners_taken')->default(0);
      $table->smallInteger('corners_won')->default(0);
      $table->float('crossing_accuracy', 5)->default(0.00);
      $table->smallInteger('drops')->default(0);
      $table->smallInteger('duels')->default(0);
      $table->smallInteger('duelslost')->default(0);
      $table->smallInteger('duelswon')->default(0);
      $table->smallInteger('fifty_fifty')->default(0);
      $table->smallInteger('foul_attempted_tackle')->default(0);
      $table->smallInteger('foul_won_penalty')->default(0);
      $table->smallInteger('g_k_successful_distribution')->default(0);
      $table->smallInteger('g_k_unsuccessful_distribution')->default(0);
      $table->smallInteger('games_played')->default(0);
      $table->smallInteger('goal_assists')->default(0);
      $table->float('goal_conversion', 5)->default(0.00);
      $table->smallInteger('goals')->default(0);
      $table->smallInteger('goals_conceded')->default(0);
      $table->smallInteger('goals_conceded_inside_box')->default(0);
      $table->smallInteger('goals_conceded_outside_box')->default(0);
      $table->smallInteger('goalsfrom_inside_box')->default(0);
      $table->smallInteger('goalsfrom_outside_box')->default(0);
      $table->smallInteger('ground_duels')->default(0);
      $table->smallInteger('ground_duelslost')->default(0);
      $table->smallInteger('ground_duelswon')->default(0);
      $table->smallInteger('handballsconceded')->default(0);
      $table->smallInteger('headed_goals')->default(0);
      $table->smallInteger('hit_woodwork')->default(0);
      $table->smallInteger('home_goals')->default(0);
      $table->smallInteger('index')->default(0);
      $table->smallInteger('interceptions')->default(0);
      $table->smallInteger('key_passes')->default(0);
      $table->smallInteger('last_man_tackle')->default(0);
      $table->smallInteger('left_foot_goals')->default(0);
      $table->smallInteger('offsides')->default(0);
      $table->smallInteger('open_play_passes')->default(0);
      $table->smallInteger('other_goals')->default(0);
      $table->smallInteger('own_goals_accrued')->default(0);
      $table->smallInteger('own_goals_conceded')->default(0);
      $table->float('passing_accuracy', 5)->default(0.00);
      $table->float('passing_per_opp_half', 5)->default(0.00);
      $table->smallInteger('penalties_conceded')->default(0);
      $table->smallInteger('penalties_off_target')->default(0);
      $table->smallInteger('penaltiestaken')->default(0);
      $table->smallInteger('penalty_goals')->default(0);
      $table->smallInteger('penalty_goals_conceded')->default(0);
      $table->smallInteger('points_droppedfrom_winning_positions')->default(0);
      $table->smallInteger('points_gainedfrom_losing_positions')->default(0);
      $table->smallInteger('possession_percentage')->default(0);
      $table->smallInteger('punches')->default(0);
      $table->smallInteger('put_through_blocked_distribution')->default(0);
      $table->smallInteger('put_through_blocked_distribution_won')->default(0);
      $table->smallInteger('recoveries')->default(0);
      $table->smallInteger('red_card')->default(0);
      $table->smallInteger('right_foot_goals')->default(0);
      $table->smallInteger('second_goal_assists')->default(0);
      $table->smallInteger('set_piece_goals')->default(0);
      $table->float('shooting_accuracy', 5)->default(0.00);
      $table->smallInteger('shots_off_target')->default(0);
      $table->smallInteger('shots_on_conceded_inside_box')->default(0);
      $table->smallInteger('shots_on_conceded_outside_box')->default(0);
      $table->smallInteger('shots_on_target')->default(0);
      $table->smallInteger('straight_red_cards')->default(0);
      $table->smallInteger('successful_cornersinto_box')->default(0);
      $table->smallInteger('successful_crossesopenplay')->default(0);
      $table->smallInteger('successful_dribbles')->default(0);
      $table->smallInteger('successful_fifty_fifty')->default(0);
      $table->smallInteger('successful_launches')->default(0);
      $table->smallInteger('successful_lay_offs')->default(0);
      $table->smallInteger('successful_long_passes')->default(0);
      $table->smallInteger('successful_open_play_passes')->default(0);
      $table->smallInteger('successful_passes_opposition_half')->default(0);
      $table->smallInteger('successful_passes_own_half')->default(0);
      $table->smallInteger('successful_short_passes')->default(0);
      $table->float('tackle_success', 5)->default(0.00);
      $table->smallInteger('tackles_lost')->default(0);
      $table->smallInteger('tackles_won')->default(0);
      $table->smallInteger('throw_insto_opposition_player')->default(0);
      $table->smallInteger('throw_insto_own_player')->default(0);
      $table->smallInteger('times_tackled')->default(0);
      $table->smallInteger('total_clearances')->default(0);
      $table->smallInteger('total_fouls_conceded')->default(0);
      $table->smallInteger('total_fouls_won')->default(0);
      $table->smallInteger('total_losses_of_possession')->default(0);
      $table->smallInteger('total_passes')->default(0);
      $table->smallInteger('total_red_cards')->default(0);
      $table->smallInteger('total_shots')->default(0);
      $table->smallInteger('total_shots_conceded')->default(0);
      $table->smallInteger('total_successful_passes')->default(0);
      $table->smallInteger('total_tackles')->default(0);
      $table->smallInteger('total_unsuccessful_passes')->default(0);
      $table->smallInteger('unsuccessful_cornersinto_box')->default(0);
      $table->smallInteger('unsuccessful_crosses_and_corners')->default(0);
      $table->smallInteger('unsuccessful_dribbles')->default(0);
      $table->smallInteger('unsuccessful_launches')->default(0);
      $table->smallInteger('unsuccessful_long_passes')->default(0);
      $table->smallInteger('unsuccessful_passes_opposition_half')->default(0);
      $table->smallInteger('unsuccessful_short_passes')->default(0);
      $table->smallInteger('unsuccessful_lay_offs')->default(0);
      $table->smallInteger('yellow_cards')->default(0);
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->index([
        'season_id',
        'team_id'
      ]);

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('opta_team_season_stats');
  }
};

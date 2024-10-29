<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('opta_player_season_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable());

      $table->float('fantasy_point', 5)->default(0);
      $table->float('rating', 5)->default(0);

      $table->smallInteger('aerial_duels')->default(0);
      $table->smallInteger('aerial_duelslost')->default(0);
      $table->smallInteger('aerial_duelswon')->default(0);
      $table->smallInteger('appearances')->default(0);
      $table->smallInteger('assists')->default(0);
      $table->smallInteger('attemptsfrom_set_pieces')->default(0);
      $table->smallInteger('away_goals')->default(0);
      $table->smallInteger('backward_passes')->default(0);
      $table->smallInteger('blocked_shots')->default(0);
      $table->smallInteger('blocks')->default(0);
      $table->smallInteger('catches')->default(0);
      $table->smallInteger('clean_sheets')->default(0);
      $table->smallInteger('clearances_offthe_line')->default(0);
      $table->smallInteger('corners_taken')->default(0);
      $table->smallInteger('corners_won')->default(0);
      $table->smallInteger('crossesnot_claimed')->default(0);
      $table->smallInteger('drops')->default(0);
      $table->smallInteger('duels')->default(0);
      $table->smallInteger('duelslost')->default(0);
      $table->smallInteger('duelswon')->default(0);
      $table->smallInteger('fifty_fifty')->default(0);
      $table->smallInteger('forward_passes')->default(0);
      $table->smallInteger('foul_attempted_tackle')->default(0);
      $table->smallInteger('g_k_successful_distribution')->default(0);
      $table->smallInteger('g_k_unsuccessful_distribution')->default(0);
      $table->smallInteger('games_played')->default(0);
      $table->smallInteger('goal_assists')->default(0);
      $table->smallInteger('goal_conversion')->default(0);
      $table->smallInteger('goalkeeper_smother')->default(0);
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
      $table->smallInteger('leftside_passes')->default(0);
      $table->smallInteger('mins_played')->default(0);
      $table->smallInteger('offsides')->default(0);
      $table->smallInteger('open_play_passes')->default(0);
      $table->smallInteger('other_goals')->default(0);
      $table->smallInteger('own_goal_scored')->default(0);
      $table->float('passing_accuracy', 5)->default(0.00);
      $table->smallInteger('penalties_conceded')->default(0);
      $table->smallInteger('penalties_faced')->default(0);
      $table->smallInteger('penalties_taken')->default(0);
      $table->smallInteger('penalty_goals')->default(0);
      $table->smallInteger('penalty_goals_conceded')->default(0);
      $table->smallInteger('punches')->default(0);
      $table->smallInteger('put_through_blocked_distribution')->default(0);
      $table->smallInteger('put_through_blocked_distribution_won')->default(0);
      $table->smallInteger('recoveries')->default(0);
      $table->smallInteger('red_card')->default(0);
      $table->smallInteger('right_foot_goals')->default(0);
      $table->smallInteger('rightside_passes')->default(0);
      $table->smallInteger('saves_made')->default(0);
      $table->smallInteger('saves_madefrom_inside_box')->default(0);
      $table->smallInteger('saves_madefrom_outside_box')->default(0);
      $table->smallInteger('savesfrom_penalty')->default(0);
      $table->smallInteger('savesmade_caught')->default(0);
      $table->smallInteger('savesmade_parried')->default(0);
      $table->smallInteger('second_goal_assist')->default(0);
      $table->smallInteger('second_goal_assists')->default(0);
      $table->smallInteger('set_piece_goals')->default(0);
      $table->smallInteger('shots_off_target')->default(0);
      $table->smallInteger('shots_on_target')->default(0);
      $table->smallInteger('starts')->default(0);
      $table->smallInteger('straight_red_cards')->default(0);
      $table->smallInteger('substitute_off')->default(0);
      $table->smallInteger('substitute_on')->default(0);
      $table->smallInteger('successful_cornersinto_box')->default(0);
      $table->smallInteger('successful_crossesand_corners')->default(0);
      $table->smallInteger('successful_crossesopenplay')->default(0);
      $table->smallInteger('successful_dribbles')->default(0);
      $table->smallInteger('successful_fifty_fifty')->default(0);
      $table->smallInteger('successful_launches')->default(0);
      $table->smallInteger('successful_lay_offs')->default(0);
      $table->smallInteger('successful_long_passes')->default(0);
      $table->smallInteger('successful_open_playpasses')->default(0);
      $table->smallInteger('successful_passes_opposition_half')->default(0);
      $table->smallInteger('successful_passes_own_half')->default(0);
      $table->smallInteger('successful_short_passes')->default(0);
      $table->smallInteger('tackles_won')->default(0);
      $table->smallInteger('through_balls')->default(0);
      $table->smallInteger('throw_insto_opposition_player')->default(0);
      $table->smallInteger('throw_insto_own_player')->default(0);
      $table->smallInteger('time_played')->default(0);
      $table->smallInteger('times_tackled')->default(0);
      $table->smallInteger('total_clearances')->default(0);
      $table->smallInteger('total_fouls_conceded')->default(0);
      $table->smallInteger('total_fouls_won')->default(0);
      $table->smallInteger('total_passes')->default(0);
      $table->smallInteger('total_red_cards')->default(0);
      $table->smallInteger('total_shots')->default(0);
      $table->smallInteger('total_successful_passes')->default(0);
      $table->smallInteger('total_tackles')->default(0);
      $table->smallInteger('total_touchesin_opposition_box')->default(0);
      $table->smallInteger('total_unsuccessful_passes')->default(0);
      $table->smallInteger('totallossesofpossession')->default(0);
      $table->smallInteger('touches_opta')->default(0);
      $table->smallInteger('unsuccessful_cornersinto_box')->default(0);
      $table->smallInteger('unsuccessful_crosses_and_corners')->default(0);
      $table->smallInteger('unsuccessful_dribbles')->default(0);
      $table->smallInteger('unsuccessful_launches')->default(0);
      $table->smallInteger('unsuccessful_long_passes')->default(0);
      $table->smallInteger('unsuccessful_passes_own_half')->default(0);
      $table->smallInteger('unsuccessful_short_passes')->default(0);
      $table->smallInteger('unsuccessful_lay_offs')->default(0);
      $table->smallInteger('winning_goal')->default(0);
      $table->smallInteger('yellow_cards')->default(0);

      // pers
      $table->float('fantasy_point_per')->default(0);
      $table->float('goals_per')->default(0);
      $table->float('goal_assists_per')->default(0);
      $table->float('total_shots_per')->default(0);
      $table->float('total_passes_per')->default(0);
      $table->float('shots_on_target_per')->default(0);
      $table->float('successful_dribbles_per')->default(0);
      $table->float('total_successful_passes_per')->default(0);
      $table->float('through_balls_per')->default(0);
      $table->float('successful_crossesopenplay_per')->default(0);
      $table->float('key_passes_per')->default(0);
      $table->float('successful_long_passes_per')->default(0);
      $table->float('total_tackles_per')->default(0);
      $table->float('interceptions_per')->default(0);
      $table->float('total_clearances_per')->default(0);
      $table->float('blocks_per')->default(0);
      $table->float('total_fouls_conceded_per')->default(0);
      $table->float('last_man_tackle_per')->default(0);
      $table->float('duelswon_per')->default(0);
      $table->float('recoveries_per')->default(0);
      $table->float('duels_per')->default(0);
      $table->float('ground_duels_per')->default(0);
      $table->float('ground_duelswon_per')->default(0);
      $table->float('aerial_duels_per')->default(0);
      $table->float('aerial_duelswon_per')->default(0);
      $table->float('saves_made_per')->default(0);
      $table->float('catches_per')->default(0);
      $table->float('punches_per')->default(0);
      $table->float('yellow_cards_per')->default(0);
      $table->float('total_red_cards_per')->default(0);

      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('opta_player_season_stats');
  }
};

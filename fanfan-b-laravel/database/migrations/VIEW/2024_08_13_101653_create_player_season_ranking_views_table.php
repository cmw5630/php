<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  public function up()
  {
    $query = <<< HEREDOC
        CREATE VIEW player_season_ranking_views AS
          SELECT season_id, player_id, 
            goals, RANK() OVER (PARTITION BY season_id ORDER BY goals DESC) AS goals_rank, total_shots, RANK() OVER (PARTITION BY season_id ORDER BY total_shots DESC) AS total_shots_rank, 
            shots_on_target, RANK() OVER (PARTITION BY season_id ORDER BY shots_on_target DESC) AS shots_on_target_rank, 
            goal_assists, RANK() OVER (PARTITION BY season_id ORDER BY goal_assists DESC) AS goal_assists_rank, 
            successful_dribbles, RANK() OVER (PARTITION BY season_id ORDER BY successful_dribbles DESC) AS successful_dribbles_rank, 
            penalty_goals, RANK() OVER (PARTITION BY season_id ORDER BY penalty_goals DESC) AS penalty_goals_rank, 
            offsides, RANK() OVER (PARTITION BY season_id ORDER BY offsides DESC) AS offsides_rank, 
            passing_accuracy, RANK() OVER (PARTITION BY season_id ORDER BY passing_accuracy DESC) AS passing_accuracy_rank,
            total_passes, RANK() OVER (PARTITION BY season_id ORDER BY total_passes DESC) AS total_passes_rank, 
            total_successful_passes, RANK() OVER (PARTITION BY season_id ORDER BY total_successful_passes DESC) AS total_successful_passes_rank, 
            through_balls, RANK() OVER (PARTITION BY season_id ORDER BY through_balls DESC) AS through_balls_rank, 
            successful_crossesopenplay, RANK() OVER (PARTITION BY season_id ORDER BY successful_crossesopenplay DESC) AS successful_crossesopenplay_rank, 
            key_passes, RANK() OVER (PARTITION BY season_id ORDER BY key_passes DESC) AS key_passes_rank, 
            successful_long_passes, RANK() OVER (PARTITION BY season_id ORDER BY successful_long_passes DESC) AS successful_long_passes_rank, 
            total_tackles, RANK() OVER (PARTITION BY season_id ORDER BY total_tackles DESC) AS total_tackles_rank, 
            interceptions, RANK() OVER (PARTITION BY season_id ORDER BY interceptions DESC) AS interceptions_rank, 
            total_clearances, RANK() OVER (PARTITION BY season_id ORDER BY total_clearances DESC) AS total_clearances_rank, 
            blocks, RANK() OVER (PARTITION BY season_id ORDER BY blocks DESC) AS blocks_rank, penalties_conceded, RANK() OVER (PARTITION BY season_id ORDER BY penalties_conceded DESC) AS penalties_conceded_rank, 
            total_fouls_conceded, RANK() OVER (PARTITION BY season_id ORDER BY total_fouls_conceded DESC) AS total_fouls_conceded_rank, 
            last_man_tackle, RANK() OVER (PARTITION BY season_id ORDER BY last_man_tackle DESC) AS last_man_tackle_rank, 
            duelswon, RANK() OVER (PARTITION BY season_id ORDER BY duelswon DESC) AS duelswon_rank, 
            recoveries, RANK() OVER (PARTITION BY season_id ORDER BY recoveries DESC) AS recoveries_rank, 
            duels, RANK() OVER (PARTITION BY season_id ORDER BY duels DESC) AS duels_rank, ground_duels, RANK() OVER (PARTITION BY season_id ORDER BY ground_duels DESC) AS ground_duels_rank, 
            ground_duelswon, RANK() OVER (PARTITION BY season_id ORDER BY ground_duelswon DESC) AS ground_duelswon_rank, 
            aerial_duels, RANK() OVER (PARTITION BY season_id ORDER BY aerial_duels DESC) AS aerial_duels_rank, 
            aerial_duelswon, RANK() OVER (PARTITION BY season_id ORDER BY aerial_duelswon DESC) AS aerial_duelswon_rank, 
            clean_sheets, RANK() OVER (PARTITION BY season_id ORDER BY clean_sheets DESC) AS clean_sheets_rank, 
            saves_made, RANK() OVER (PARTITION BY season_id ORDER BY saves_made DESC) AS saves_made_rank, 
            savesfrom_penalty, RANK() OVER (PARTITION BY season_id ORDER BY savesfrom_penalty DESC) AS savesfrom_penalty_rank, 
            catches, RANK() OVER (PARTITION BY season_id ORDER BY catches DESC) AS catches_rank, 
            punches, RANK() OVER (PARTITION BY season_id ORDER BY punches DESC) AS punches_rank, 
            yellow_cards, RANK() OVER (PARTITION BY season_id ORDER BY yellow_cards DESC) AS yellow_cards_rank, 
            total_red_cards, RANK() OVER (PARTITION BY season_id ORDER BY total_red_cards DESC) AS total_red_cards_rank 
          FROM %s.`opta_player_season_stats` 
          WHERE %s.`opta_player_season_stats`.`deleted_at` IS NULL 
        HEREDOC;

    $query = sprintf(
      $query,
      config('database.connections.data.database'),
      config('database.connections.data.database')
    );

    DB::unprepared($query);
  }

  public function down()
  {
    DB::unprepared('DROP VIEW IF EXISTS player_season_ranking_views');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  public function up()
  {
    $query = <<< HEREDOC
        CREATE VIEW player_overall_avg_views AS
          SELECT season_id, player_id,sub_position,
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.gk')),0) AS gk, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.cb')),0) AS cb, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.lb')),0) AS lb, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.rb')),0) AS rb, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.dm')),0) AS dm, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.lwb')),0) AS lwb, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.rwb')),0) AS rwb, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.cm')),0) AS cm, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.lm')),0) AS lm, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.rm')),0) AS rm, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.am')),0) AS am, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.lw')),0) AS lw, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.rw')),0) AS rw, 
            ROUND(AVG(JSON_EXTRACT(final_overall, '$.st')),0) AS st,
            ROUND(AVG(JSON_EXTRACT(shot, '$.overall')), 1) AS shot,
            ROUND(AVG(JSON_EXTRACT(finishing, '$.overall')), 1) AS finishing,
            ROUND(AVG(JSON_EXTRACT(dribbles, '$.overall')), 1) AS dribbles,
            ROUND(AVG(JSON_EXTRACT(positioning, '$.overall')), 1) AS positioning,
            ROUND(AVG(JSON_EXTRACT(passing, '$.overall')), 1) AS passing,
            ROUND(AVG(JSON_EXTRACT(chance_create, '$.overall')), 1) AS chance_create,
            ROUND(AVG(JSON_EXTRACT(long_pass, '$.overall')), 1) AS long_pass,
            ROUND(AVG(JSON_EXTRACT(crosses, '$.overall')), 1) AS crosses,
            ROUND(AVG(JSON_EXTRACT(tackles, '$.overall')), 1) AS tackles,
            ROUND(AVG(JSON_EXTRACT(blocks, '$.overall')), 1) AS blocks,
            ROUND(AVG(JSON_EXTRACT(clearances, '$.overall')), 1) AS clearances,
            ROUND(AVG(JSON_EXTRACT(instinct, '$.overall')), 1) AS instinct,
            ROUND(AVG(JSON_EXTRACT(ground_duels, '$.overall')), 1) AS ground_duels,
            ROUND(AVG(JSON_EXTRACT(aerial_duels, '$.overall')), 1) AS aerial_duels,
            ROUND(AVG(JSON_EXTRACT(interceptions, '$.overall')), 1) AS interceptions,
            ROUND(AVG(JSON_EXTRACT(recoveries, '$.overall')), 1) AS recoveries,
            ROUND(AVG(JSON_EXTRACT(saves, '$.overall')), 1) AS saves,
            ROUND(AVG(JSON_EXTRACT(high_claims, '$.overall')), 1) AS high_claims,
            ROUND(AVG(JSON_EXTRACT(sweeper, '$.overall')), 1) AS sweeper,
            ROUND(AVG(JSON_EXTRACT(punches, '$.overall')), 1) AS punches,
            ROUND(AVG(JSON_EXTRACT(speed, '$.overall')), 1) AS speed,
            ROUND(AVG(JSON_EXTRACT(balance, '$.overall')), 1) AS balance,
            ROUND(AVG(JSON_EXTRACT(power, '$.overall')), 1) AS power,
            ROUND(AVG(JSON_EXTRACT(stamina, '$.overall')), 1) AS stamina
          FROM %s.`overalls` 
          WHERE %s.`overalls`.`deleted_at` IS NULL 
          GROUP BY `season_id`,`player_id`,`sub_position`
        HEREDOC;

    $query = sprintf(
      $query,
      config('database.connections.simulation.database'),
      config('database.connections.simulation.database')
    );

    DB::unprepared($query);
  }

  public function down()
  {
    DB::unprepared('DROP VIEW IF EXISTS player_overall_avg_views');
  }
};

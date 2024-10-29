<?php

namespace Database\Seeders;

use App\Models\meta\RefTeamTierBonus;
use Illuminate\Database\Seeder;
use Schema;

class RefTeamTierBonusesSeeder extends Seeder
{

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (RefTeamTierBonus::all()->isNotEmpty()) {
      return;
    }
    $seedData = [
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '80foo89mm28qjvyhjzlpwj28k',
        'season_name' => '2022/2023',
        'league_name' => 'Premier League',
        'data' => [
          ['team_id' => 'a3nyxabgsqlnqfkeg41m6tnpp', 'team_name' =>  'Man City', 'rank' => 1, 'normalized_bonus' => 0.05],
          ['team_id' => 'c8h9bw1l82s06h77xxrelzhur', 'team_name' =>  'Liverpool', 'rank' => 2, 'normalized_bonus' => 0.04],
          ['team_id' => '9q0arba2kbnywth8bkxlhgmdr', 'team_name' =>  'Chelsea', 'rank' => 3, 'normalized_bonus' => 0.03],
          ['team_id' => '6eqit8ye8aomdsrrq0hk3v7gh', 'team_name' =>  'Man Utd', 'rank' => 4, 'normalized_bonus' => 0.025],
          ['team_id' => '4dsgumo7d4zupm2ugsvm4zm4d', 'team_name' =>  'Arsenal', 'rank' => 5, 'normalized_bonus' => 0.023],
          ['team_id' => '22doj4sgsocqpxw45h607udje', 'team_name' =>  'Tottenham', 'rank' => 6, 'normalized_bonus' => 0.023],
          ['team_id' => '7vn2i2kd35zuetw6b38gw9jsz', 'team_name' =>  'Newcastle', 'rank' => 7, 'normalized_bonus' => 0.021],
          ['team_id' => 'avxknfz4f6ob0rv9dbnxdzde0', 'team_name' =>  'Leicester', 'rank' => 8, 'normalized_bonus' => 0.019],
          ['team_id' => '4txjdaqveermfryvbfrr4taf7', 'team_name' =>  'West Ham', 'rank' => 9, 'normalized_bonus' => 0.016],
          ['team_id' => 'b9si1jn1lfxfund69e9ogcu2n', 'team_name' =>  'Wolves', 'rank' => 10, 'normalized_bonus' => 0.015],
          ['team_id' => 'ehd2iemqmschhj2ec0vayztzz', 'team_name' =>  'Everton', 'rank' => 11, 'normalized_bonus' => 0.013],
          ['team_id' => 'e5p0ehyguld7egzhiedpdnc3w', 'team_name' =>  'Brighton', 'rank' => 12, 'normalized_bonus' => 0.013],
          ['team_id' => 'b496gs285it6bheuikox6z9mj', 'team_name' =>  'Aston Villa', 'rank' => 13, 'normalized_bonus' => 0.010],
          ['team_id' => '1c8m2ko0wxq1asfkuykurdr0y', 'team_name' =>  'Crystal Palace', 'rank' => 14, 'normalized_bonus' => 0.010],
          ['team_id' => '7yx5dqhhphyvfisohikodajhv', 'team_name' =>  'Brentford', 'rank' => 15, 'normalized_bonus' => 0.008],
          ['team_id' => 'd5ydtvt96bv7fq04yqm2w2632', 'team_name' =>  'Southampton', 'rank' => 16, 'normalized_bonus' => 0.008],
          ['team_id' => '48gk2hpqtsl6p9sx9kjhaydq4', 'team_name' =>  'Leeds', 'rank' => 17, 'normalized_bonus' => 0.006],
          ['team_id' => 'hzqh7z0mdl3v7gwete66syxp', 'team_name' =>  'Fulham', 'rank' => 18, 'normalized_bonus' => 0.003],
          ['team_id' => '1pse9ta7a45pi2w2grjim70ge', 'team_name' =>  'Bournemouth', 'rank' => 19, 'normalized_bonus' => 0.003],
          ['team_id' => '1qtaiy11gswx327s0vkibf70n', 'team_name' =>  'Nottm Forest', 'rank' => 20, 'normalized_bonus' => 0.003],
        ],
      ]
    ];

    foreach ($seedData as $leagueTeams) {
      foreach ($leagueTeams['data'] as $temaData) {
        Schema::connection('api')->disableForeignKeyConstraints();
        RefTeamTierBonus::insert(
          array_merge(
            $temaData,
            [
              'league_id' => $leagueTeams['league_id'],
              'season_id' => $leagueTeams['season_id'],
              'season_name' => $leagueTeams['season_name'],
              'league_name' => $leagueTeams['league_name']
            ]
          ),
        );
        Schema::connection('api')->enableForeignKeyConstraints();
      }
    }
  }
}

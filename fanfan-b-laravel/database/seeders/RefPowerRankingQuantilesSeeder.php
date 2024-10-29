<?php

namespace Database\Seeders;

use App\Models\meta\RefPowerRankingQuantile;
use App\Models\meta\RefPriceGradeTransformMap;
use Illuminate\Database\Seeder;
use Schema;

class RefPowerRankingQuantilesSeeder extends Seeder
{

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (RefPowerRankingQuantile::all()->isNotEmpty()) {
      return;
    }

    $normalizedValueMap = [
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'league_name' => 'Premier League',
        'season_id' => '80foo89mm28qjvyhjzlpwj28k',
        'season_name' => '2022/2023',
        'mean' => 6.18194619809204,
        'stdev' => 9.84735922228328,
        'power_ranking' => [
          1 =>  39.36,
          2 =>  31.94,
          3 =>  27.77,
          4 =>  23.61,
          5 =>  20.66,
          6 =>  17.74,
          7 =>  13.63,
          8 =>  10.81,
          9 =>  9.27,
          10 => 7.91,
          11 => 5.81,
          12 => 4.08,
          13 => 1.18,
          14 => 0,
          15 => -3,
          16 => null,
        ],
        'normalized_cut_point' =>  [
          1 =>    3.3692336242624700,
          2 =>    2.6157321186801900,
          3 =>    2.1922683345456700,
          4 =>    1.7698200510925400,
          5 =>    1.4702473500861000,
          6 =>    1.1737211511238100,
          7 =>    0.7563503710775560,
          8 =>    0.4699791789290350,
          9 =>    0.3135920739968640,
          10 =>   0.1754839813294920,
          11 =>   -0.0377711617598328,
          12 =>   -0.2134527796381810,
          13 =>   -0.5079479772377240,
          14 =>   -0.6277770576402970,
          15 =>   -0.9324272620536170,
          16 => -999,
        ],
      ],
    ];


    $seedData = [];

    $mapIdentificationIds = RefPriceGradeTransformMap::pluck('id')->toArray();
    foreach ($normalizedValueMap as $item) {
      foreach ($mapIdentificationIds as $id) {
        $seedData[] = [
          'league_id' => $item['league_id'],
          'league_name' => $item['league_name'],
          'map_identification_id' => $id,
          'power_ranking' => $item['power_ranking'][$id],
          'normalized_value' => $item['normalized_cut_point'][$id],
          'mean' => $item['mean'],
          'stdev' => $item['stdev'],
        ];
      }
    }

    Schema::disableForeignKeyConstraints();
    foreach ($seedData as $row) {
      RefPowerRankingQuantile::insert(
        $row
      );
    }
    Schema::enableForeignKeyConstraints();
  }
}

<?php

namespace Database\Seeders;

use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimulationTierSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $seedData = [
      [
        'level' => 1,
        'name' => 'Premier',
      ],
      [
        'level' => 2,
        'name' => 'Champions',
      ],
      [
        'level' => 3,
        'name' => 'Elite',
      ],
      [
        'level' => 4,
        'name' => 'Pro',
      ],
      [
        'level' => 5,
        'name' => 'Advanced',
      ],
      [
        'level' => 6,
        'name' => 'Rookie',
      ],
    ];

    foreach ($seedData as $row) {
      SimulationTier::updateOrCreateEx([
        'level' => $row['level'],
      ], [
        'name' => $row['name'],
      ]);
    }

    $tiers = SimulationTier::orderByDesc('level')->get();
    foreach ($tiers as $tier) {
      $maxLeagueCount = null;
      // 루키 = 1, 나머지 5~1
      $divisionNo = ($tier->level === 6) ? 1 : 5;
      while($divisionNo >= 1) {
        if ($tier->level === 1) {
          $maxLeagueCount = $divisionNo;
        }
        SimulationDivision::updateOrCreateEx([
          'division_no' => $divisionNo--,
          'tier_id' => $tier->id,
        ], [
          'max_league_count' => $maxLeagueCount,
        ]);
      }
    }
  }
}

<?php

namespace Database\Seeders;

use App\Models\game\RefTransferValue;
use Illuminate\Database\Seeder;
use Schema;

class RefTransfersSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  public function run()
  {
    $seedData = [
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'league_name' => 'Serie A',
        'league_country' => 'Italy',
        'value' => 0,
        'v_bonus' => 0,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'league_name' => 'Serie A',
        'league_country' => 'Italy',
        'value' => 30000000,
        'v_bonus' => 3.5,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'league_name' => 'Serie A',
        'league_country' => 'Italy',
        'value' => 50000000,
        'v_bonus' => 4,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'league_name' => 'Serie A',
        'league_country' => 'Italy',
        'value' => 70000000,
        'v_bonus' => 5,
      ],
    ];
    Schema::disableForeignKeyConstraints();
    foreach ($seedData as $idx => $row) {
      RefTransferValue::updateOrCreateEx([
        'league_id' => $row['league_id'],
        'v_bonus' => $row['v_bonus'],
      ], $row);
    }
    Schema::enableForeignKeyConstraints();
  }
}

<?php

namespace Database\Seeders;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Card\OriginGrade;
use App\Libraries\Classes\FantasyCalculator;
use App\Models\meta\RefDraftPrice;
use Illuminate\Database\Seeder;
use Schema;

class RefDraftPricesSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  public function run()
  {
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $initPriceSet = array(
      1 => 10000,
      2 => 20000,
      3 => 30000,
      4 => 40000,
      5 => 80000,
      6 => 120000,
      7 => 240000,
      8 => 480000,
      9 => 960000,
    );

    $seedData = $draftCalculator->makeDraftPriceTableData($initPriceSet);

    foreach ($seedData as $idx => $row) {
      RefDraftPrice::updateOrCreateEx([
        'level' => $row['level'],
      ], $row);
    }
  }
}

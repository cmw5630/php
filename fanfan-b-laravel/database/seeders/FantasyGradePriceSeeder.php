<?php

namespace Database\Seeders;

use App\Enums\Opta\Card\OriginGrade;
use App\Models\meta\RefPlateGradePrice;
use Illuminate\Database\Seeder;

class FantasyGradePriceSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  public function run()
  {
    if (RefPlateGradePrice::all()->isNotEmpty()) {
      return;
    }

    $seedData = [
      [
        'percentile_point' => 0,
        'grade' => OriginGrade::SS,
        'price' => 120000,
      ],
      [
        'percentile_point' => 2,
        'grade' => OriginGrade::S,
        'price' => 90000,
      ],
      [
        'percentile_point' => 9,
        'grade' => OriginGrade::A,
        'price' => 70000,
      ],
      [
        'percentile_point' => 23,
        'grade' => OriginGrade::B,
        'price' => 50000,
      ],
      [
        'percentile_point' => 43,
        'grade' => OriginGrade::C,
        'price' => 30000,
      ],
      [
        'percentile_point' => 70,
        'grade' => OriginGrade::D,
        'price' => 10000,
      ],
    ];
    foreach ($seedData as $idx => $row) {
      RefPlateGradePrice::updateOrCreateEx([
        'grade' => $row['grade'],
      ], $row);
    }
  }
}

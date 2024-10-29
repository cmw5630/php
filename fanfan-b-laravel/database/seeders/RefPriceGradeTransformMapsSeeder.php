<?php

namespace Database\Seeders;

use App\Enums\Opta\Card\OriginGrade;
use App\Models\data\League;
use App\Models\meta\RefPowerRankingQuantile;
use App\Models\meta\RefPriceGradeTransformMap;
use Google\Service\Transcoder\OriginUri;
use Illuminate\Database\Seeder;


class RefPriceGradeTransformMapsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (RefPriceGradeTransformMap::all()->isNotEmpty()) {
      return;
    }
    $seedData = [
      [
        'id' => 1,
        OriginGrade::S => OriginGrade::SS,
        OriginGrade::A => OriginGrade::S,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 2,
        OriginGrade::S => OriginGrade::SS,
        OriginGrade::A => OriginGrade::S,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 3,
        OriginGrade::S => OriginGrade::SS,
        OriginGrade::A => OriginGrade::S,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 4,
        OriginGrade::A => OriginGrade::S,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 5,
        OriginGrade::A => OriginGrade::S,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 6,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 7,
        OriginGrade::B => OriginGrade::A,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 8,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 9,
        OriginGrade::C => OriginGrade::B,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 10,
        OriginGrade::D => OriginGrade::C,
      ],
      [
        'id' => 11,
      ],
      [
        'id' => 12,
      ],
      [
        'id' => 13,
      ],
      [
        'id' => 14,
        OriginGrade::SS => OriginGrade::S,
        OriginGrade::S => OriginGrade::A,
        OriginGrade::A => OriginGrade::B,
        OriginGrade::B => OriginGrade::C,
      ],
      [
        'id' => 15,
        OriginGrade::SS => OriginGrade::S,
        OriginGrade::S => OriginGrade::A,
        OriginGrade::A => OriginGrade::B,
        OriginGrade::B => OriginGrade::C,
        OriginGrade::C => OriginGrade::D,
      ],
      [
        'id' => 16,
        OriginGrade::SS => OriginGrade::S,
        OriginGrade::S => OriginGrade::A,
        OriginGrade::A => OriginGrade::B,
        OriginGrade::B => OriginGrade::C,
        OriginGrade::C => OriginGrade::D,
      ],
    ];

    foreach ($seedData as $row) {
      RefPriceGradeTransformMap::insert(
        $row
      );
    }
  }
}

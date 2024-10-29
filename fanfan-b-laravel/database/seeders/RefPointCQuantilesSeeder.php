<?php

namespace Database\Seeders;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Season\SeasonNameType;
use App\Models\meta\RefPointcQuantile;
use Illuminate\Database\Seeder;

class RefPointCQuantilesSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  public function run()
  {
    if (RefPointcQuantile::all()->isNotEmpty()) {
      return;
    }

    $seedData = [
      [
        'playing_season_name' => '2020/2021',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.2,
        'quantile_middle' => 16,
        'quantile_bottom' => 8.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2020/2021',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 21.6,
        'quantile_middle' => 13.8,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2020/2021',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 22.3,
        'quantile_middle' => 13.6,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2020/2021',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 17.8,
        'quantile_middle' => 8.7,
        'quantile_bottom' => 3.2,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021/2022',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.2,
        'quantile_middle' => 16,
        'quantile_bottom' => 8.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021/2022',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 21.6,
        'quantile_middle' => 13.8,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021/2022',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 22.3,
        'quantile_middle' => 13.6,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021/2022',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 17.8,
        'quantile_middle' => 8.7,
        'quantile_bottom' => 3.2,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022/2023',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.2,
        'quantile_middle' => 16,
        'quantile_bottom' => 8.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022/2023',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 21.6,
        'quantile_middle' => 13.8,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022/2023',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 22.3,
        'quantile_middle' => 13.6,
        'quantile_bottom' => 6,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022/2023',
        'season_name_type' => SeasonNameType::DOUBLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 17.8,
        'quantile_middle' => 8.7,
        'quantile_bottom' => 3.2,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.5,
        'quantile_middle' => 16.4,
        'quantile_bottom' => 9.3,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 22,
        'quantile_middle' => 14.4,
        'quantile_bottom' => 6.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 21.2,
        'quantile_middle' => 12.9,
        'quantile_bottom' => 5.9,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2021',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 15.7,
        'quantile_middle' => 7.9,
        'quantile_bottom' => 3.1,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.5,
        'quantile_middle' => 16.4,
        'quantile_bottom' => 9.3,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 22,
        'quantile_middle' => 14.4,
        'quantile_bottom' => 6.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 21.2,
        'quantile_middle' => 12.9,
        'quantile_bottom' => 5.9,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2022',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 15.7,
        'quantile_middle' => 7.9,
        'quantile_bottom' => 3.1,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2023',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::GOALKEEPER,
        'quantile_top' => 23.5,
        'quantile_middle' => 16.4,
        'quantile_bottom' => 9.3,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2023',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::DEFENDER,
        'quantile_top' => 22,
        'quantile_middle' => 14.4,
        'quantile_bottom' => 6.8,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2023',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::MIDFIELDER,
        'quantile_top' => 21.2,
        'quantile_middle' => 12.9,
        'quantile_bottom' => 5.9,
        'base_offset' => 3,
      ],
      [
        'playing_season_name' => '2023',
        'season_name_type' => SeasonNameType::SINGLE,
        'position' => PlayerPosition::ATTACKER,
        'quantile_top' => 15.7,
        'quantile_middle' => 7.9,
        'quantile_bottom' => 3.1,
        'base_offset' => 3,
      ],
    ];
    foreach ($seedData as $idx => $row) {
      RefPointcQuantile::updateOrCreateEx([
        'playing_season_name' => $row['playing_season_name'],
        'position' => $row['position'],
      ], $row);
    }
  }
}

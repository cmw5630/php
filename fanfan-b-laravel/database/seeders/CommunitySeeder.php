<?php

namespace Database\Seeders;

use App\Enums\BoardType;
use App\Models\community\Board;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunitySeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (Board::all()->isNotEmpty()) {
      return;
    }

    $boardData = [
      [
        'type' => BoardType::INFO,
        'name' => 'notice'
      ],
      [
        'type' => BoardType::COMMUNITY,
        'name' => 'free',
      ]
    ];

    Board::insert($boardData);
  }
}

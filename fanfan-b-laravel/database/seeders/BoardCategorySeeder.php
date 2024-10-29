<?php

namespace Database\Seeders;

use App\Models\community\Board;
use App\Models\community\BoardCategory;
use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationTier;
use Illuminate\Database\Seeder;

class BoardCategorySeeder extends Seeder
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
        'name' => 'free',
        'category' => [
          'General',
          'Ask',
          'Analysis'
        ]
      ],
      [
        'name' => 'sports',
        'category' => [
          'Football',
          'Basketball',
          'Baseball',
          'Ask',
          'Analysis'
        ]
      ],
      [
        'name' => 'notice',
        'category' => [
          'Notice',
          'Update',
        ]
      ],
    ];

    foreach ($seedData as $row) {
      [$boardId,] = Board::updateOrCreateEx([
        'name' => $row['name'],
      ], []);

      foreach ($row['category'] as $category) {
        BoardCategory::updateOrCreateEx([
          'board_id' => $boardId,
          'name' => $category
        ], []);
      }
    }
  }
}

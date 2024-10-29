<?php

namespace Database\Seeders;

use App\Enums\QuestCollectionType;
use App\Enums\QuestCycleType;
use App\Models\game\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
  public function run()
  {
    $seedData = [
      [
        'code' => QuestCollectionType::LOGIN,
        'name' => '로그인 성공',
        'period' => QuestCycleType::WEEKLY
      ],
      [
        'code' => QuestCollectionType::PLATE_BUY,
        'name' => '플레이트 카드 구매',
        'period' => QuestCycleType::WEEKLY
      ],
      [
        'code' => QuestCollectionType::UPGRADE,
        'name' => '플레이트 카드 강화',
        'period' => QuestCycleType::WEEKLY
      ],
      [
        'code' => QuestCollectionType::PARTICIPATION,
        'name' => '게임 참여',
        'period' => QuestCycleType::WEEKLY
      ],
    ];

    foreach ($seedData as $idx => $row) {
      Quest::updateOrCreateEx([
        'code' => $row['code'],
        'name' => $row['name'],
        'period' => $row['period'],
      ], $row);
    }
  }
}

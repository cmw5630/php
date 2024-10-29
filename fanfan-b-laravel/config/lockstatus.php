<?php

use App\Enums\GradeCardLockStatus;

return [
  'enter' => [
    GradeCardLockStatus::INGAME => [
      null => GradeCardLockStatus::INGAME,
      GradeCardLockStatus::INGAME => GradeCardLockStatus::INGAME,
      GradeCardLockStatus::MARKET => false,
      GradeCardLockStatus::SIMULATION => GradeCardLockStatus::INGAME_SIMULATION,
      GradeCardLockStatus::INGAME_SIMULATION => GradeCardLockStatus::INGAME_SIMULATION,
    ],
    GradeCardLockStatus::MARKET => [
      null => GradeCardLockStatus::MARKET,
      GradeCardLockStatus::INGAME => false,
      GradeCardLockStatus::MARKET => false,
      GradeCardLockStatus::SIMULATION => false,
      GradeCardLockStatus::INGAME_SIMULATION => false,
    ],
    GradeCardLockStatus::SIMULATION => [
      null => GradeCardLockStatus::SIMULATION,
      GradeCardLockStatus::INGAME => GradeCardLockStatus::INGAME_SIMULATION,
      GradeCardLockStatus::MARKET => false,
      GradeCardLockStatus::SIMULATION => GradeCardLockStatus::SIMULATION,
      GradeCardLockStatus::INGAME_SIMULATION => GradeCardLockStatus::INGAME_SIMULATION,
    ],
  ],
  'exit' => [
    // babo 인 경우는 상태오류.(생기면 안됨)
    GradeCardLockStatus::INGAME => [
      null => 'babo',
      GradeCardLockStatus::INGAME => null,
      GradeCardLockStatus::MARKET => 'babo',
      GradeCardLockStatus::SIMULATION => 'babo',
      GradeCardLockStatus::INGAME_SIMULATION => GradeCardLockStatus::SIMULATION,
    ],
    GradeCardLockStatus::MARKET => [
      null => 'babo',
      GradeCardLockStatus::INGAME => 'babo',
      GradeCardLockStatus::MARKET => null,
      GradeCardLockStatus::SIMULATION => 'babo',
      GradeCardLockStatus::INGAME_SIMULATION => 'babo',
    ],
    GradeCardLockStatus::SIMULATION => [
      null => 'babo',
      GradeCardLockStatus::INGAME => 'babo',
      GradeCardLockStatus::MARKET => 'babo',
      GradeCardLockStatus::SIMULATION => null,
      GradeCardLockStatus::INGAME_SIMULATION => GradeCardLockStatus::INGAME,
    ],
  ]
];

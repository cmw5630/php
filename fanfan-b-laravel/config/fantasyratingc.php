<?php

use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;

$commonTable = [
  'general' => 1.1,
  'offensive' => 1.1,
  'duel' => 1.2,
  'goalkeeping' => 0,
];

$specifiedTables = [
  'position' => [
    PlayerDailyPosition::STRIKER => [
      'offensive' => 1.5,
      'passing' => 1.2,
      'defensive' => 1,
    ],
    PlayerDailyPosition::ATTACKING_MIDFIELDER => [
      'passing' => 1.5,
      'defensive' => 1.1,
    ],
    PlayerDailyPosition::MIDFIELDER => [
      'passing' => 1.5,
      'defensive' => 1.1,
    ],
    PlayerDailyPosition::DEFENSIVE_MIDFIELDER => [
      'passing' => 1.5,
      'defensive' => 1.1,
    ],
    PlayerDailyPosition::DEFENDER => [
      'passing' => 1.1,
      'defensive' => 1.5,
    ],
    PlayerDailyPosition::WING_BACK => [
      'passing' => 1.1,
      'defensive' => 1.5,
    ],
    PlayerDailyPosition::GOALKEEPER => [
      'offensive' => 0,
      'passing' => 1,
      'defensive' => 1.2,
      'goalkeeping' => 1.5,
    ],
  ],
  'sub_position' => [
    PlayerPosition::ATTACKER => [
      'offensive' => 1.5,
      'passing' => 1.2,
      'defensive' => 1,
    ],
    PlayerPosition::MIDFIELDER => [
      'passing' => 1.5,
      'defensive' => 1.1,
    ],
    PlayerPosition::DEFENDER => [
      'passing' => 1.1,
      'defensive' => 1.5,
    ],
    PlayerPosition::GOALKEEPER => [
      'offensive' => 0,
      'passing' => 1,
      'defensive' => 1.2,
      'goalkeeping' => 1.5,
    ],
  ]
];

return [
  'FANTASYRATINGC_REFERENCE_TABLE_V0' => [
    'Policy' => [],
    'CombTable' => [
      'position' => [
        PlayerDailyPosition::STRIKER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::STRIKER]),
        PlayerDailyPosition::ATTACKING_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::ATTACKING_MIDFIELDER]),
        PlayerDailyPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::MIDFIELDER]),
        PlayerDailyPosition::DEFENSIVE_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENSIVE_MIDFIELDER]),
        PlayerDailyPosition::WING_BACK => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::WING_BACK]),
        PlayerDailyPosition::DEFENDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENDER]),
        PlayerDailyPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::GOALKEEPER]),
      ],
      'sub_position' => [
        PlayerPosition::ATTACKER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::ATTACKER]),
        PlayerPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::MIDFIELDER]),
        PlayerPosition::DEFENDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::DEFENDER]),
        PlayerPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::GOALKEEPER]),
      ]
    ],
  ]
];

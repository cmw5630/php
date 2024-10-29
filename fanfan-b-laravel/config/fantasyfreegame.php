<?php

use App\Enums\Opta\Player\PlayerPosition;

return [
  'FANTASYFREEGAME_REFERENCE_TABLE_V0' => [
    'shufflePointCost' => 200,
    'shuffleCountMax' => 3,
    'gradeMap' => [
      's' => 0,
      'a' => 20,
      'b' => 50,
      'c' => 75,
      'd' => 90,
    ],
    'momPerCentPoints' => [
      'field' => [
        's' => 3542,
        'a' => 2831,
        'b' => 2063,
        'c' => 1142,
        'd' => 532,
      ],
      'keeper' => [
        's' => 3270,
        'a' => 2601,
        'b' => 1986,
        'c' => 946,
        'd' => 386,
      ],
    ],
    'threeStrengthPercentMap' => [
      'attacking' => [
        PlayerPosition::ATTACKER => 7.3334,
        PlayerPosition::MIDFIELDER => 1.99993,
        PlayerPosition::DEFENDER => 0.0001,
        PlayerPosition::GOALKEEPER => 0,
      ],
      'goalkeeping' => [
        PlayerPosition::ATTACKER => 0,
        PlayerPosition::MIDFIELDER => 0,
        PlayerPosition::DEFENDER => 0,
        PlayerPosition::GOALKEEPER => 9.65556,
      ],
      'passing' => [
        PlayerPosition::ATTACKER => 1.20847,
        PlayerPosition::MIDFIELDER => 4.04716,
        PlayerPosition::DEFENDER => 5.52038,
        PlayerPosition::GOALKEEPER => 1.24621,
      ],
      'defensive' => [
        PlayerPosition::ATTACKER => 0.0001,
        PlayerPosition::MIDFIELDER => 0.10664,
        PlayerPosition::DEFENDER => 7.7267,
        PlayerPosition::GOALKEEPER => 0.0001,
      ],
      'duel' => [
        PlayerPosition::ATTACKER => 3.50552,
        PlayerPosition::MIDFIELDER => 8.35949,
        PlayerPosition::DEFENDER => 6.78499,
        PlayerPosition::GOALKEEPER => 0.0001,
      ],
    ]
  ]
];

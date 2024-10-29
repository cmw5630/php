<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;

return [
  'server' => [
    'europe' => [
      'timezone' => 'GMT',
    ],
    'asia' => [
      'timezone' => 'Asia/Seoul',
    ],
  ],
  'substitution_sub_position' => [
    PlayerPosition::ATTACKER => [
      PlayerSubPosition::ST,
      PlayerSubPosition::LW,
      PlayerSubPosition::RW
    ],
    PlayerPosition::MIDFIELDER => [
      PlayerSubPosition::AM,
      PlayerSubPosition::CM,
      PlayerSubPosition::DM,
      PlayerSubPosition::LM,
      PlayerSubPosition::RM,
    ],
    PlayerPosition::DEFENDER => [
      PlayerSubPosition::CB,
      PlayerSubPosition::LB,
      PlayerSubPosition::RB,
    ],
    PlayerPosition::GOALKEEPER => [
      PlayerSubPosition::GK,
    ]
  ],
  'substitution_count_formations' => [
    '343' => [
      PlayerPosition::DEFENDER => 1,
      PlayerPosition::MIDFIELDER => 2,
      PlayerPosition::ATTACKER => 2,
    ],
    '433' => [
      PlayerPosition::DEFENDER => 1,
      PlayerPosition::MIDFIELDER => 2,
      PlayerPosition::ATTACKER => 2,
    ],
    '3241' => [
      PlayerPosition::DEFENDER => 1,
      PlayerPosition::MIDFIELDER => 3,
      PlayerPosition::ATTACKER => 1,
    ],
    '352' => [
      PlayerPosition::DEFENDER => 1,
      PlayerPosition::MIDFIELDER => 3,
      PlayerPosition::ATTACKER => 1,
    ],
    '4231' => [
      PlayerPosition::DEFENDER => 2,
      PlayerPosition::MIDFIELDER => 2,
      PlayerPosition::ATTACKER => 1,
    ],
    '442' => [
      PlayerPosition::DEFENDER => 2,
      PlayerPosition::MIDFIELDER => 2,
      PlayerPosition::ATTACKER => 1,
    ],
    '532' => [
      PlayerPosition::DEFENDER => 2,
      PlayerPosition::MIDFIELDER => 2,
      PlayerPosition::ATTACKER => 1,
    ],
  ],
  'substitution_minuites_policies' => [
    4 =>  ['min' => 35, 'max' => 90], // (home, away) 교체 총 합 4명이상
    0 =>  ['min' => 65, 'max' => 85], // (home, away) 교체 총 합 3명이하
  ],
  'stamina_reducement' => [
    PlayerSubPosition::GK => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '0' => -0.3,
      ]
    ],
    PlayerSubPosition::CB => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.3,
        '76' => -0.4,
        '61' => -0.5,
        '51' => -0.6,
        '46' => -0.7,
        '0' => -0.8,
      ],
    ],
    PlayerSubPosition::LB => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '81' => -0.5,
        '71' => -0.6,
        '61' => -0.7,
        '46' => -0.8,
        '0' => -0.9,
      ],
    ],
    PlayerSubPosition::RB => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '81' => -0.5,
        '71' => -0.6,
        '61' => -0.7,
        '46' => -0.8,
        '0' => -0.9,
      ],
    ],
    PlayerSubPosition::LWB => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '81' => -0.5,
        '71' => -0.6,
        '61' => -0.7,
        '46' => -0.8,
        '0' => -0.9,
      ],
    ],
    PlayerSubPosition::RWB => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '81' => -0.5,
        '71' => -0.6,
        '61' => -0.7,
        '46' => -0.8,
        '0' => -0.9,
      ],
    ],
    PlayerSubPosition::CM => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '86' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::DM => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.4,
        '86' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::AM => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '86' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::LM => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::RM => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::LW => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::RW => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '91' => -0.5,
        '76' => -0.6,
        '66' => -0.7,
        '56' => -0.8,
        '46' => -0.9,
        '0' => -1,
      ],
    ],
    PlayerSubPosition::ST => [
      'dev' => [
        'under' => -0.1,
        'upper' => 0.1
      ],
      'policies' => [
        '81' => -0.5,
        '71' => -0.6,
        '61' => -0.7,
        '46' => -0.8,
        '0' => -0.9,
      ],
    ],
  ],
  'report_player' => [
    PlayerPosition::ATTACKER => 8,
    PlayerPosition::MIDFIELDER => 10,
    PlayerPosition::DEFENDER => 10,
    PlayerPosition::GOALKEEPER => 2,
  ]
];

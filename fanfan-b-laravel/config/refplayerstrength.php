<?php

use App\Enums\PlayerStrengthStandardType;
use App\Enums\PlayerStrengthType;

return [
  'categories' => [
    'Attacking' => [
      'finishing' => [
        'col_name' => ['goals',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 0.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 0.28
          ]
        ],
        'standard' => PlayerStrengthStandardType::AVERAGE
        // 'order_position' => [PlayerPosition::ATTACKER, PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::GOALKEEPER],
      ],
      'dribbling' => [
        'col_name' => ['won_contest',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 2.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 1.8
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::ATTACKER, PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::GOALKEEPER],
      ],
      'shooting' => [
        'col_name' => ['total_scoring_att',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 3.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 2.6
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::ATTACKER, PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::GOALKEEPER],
      ],
      'assists' => [
        'col_name' => ['goal_assist',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 0.35
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 0.2
          ]
        ],
        'standard' => PlayerStrengthStandardType::AVERAGE
        // 'order_position' => [PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER, PlayerPosition::DEFENDER, PlayerPosition::GOALKEEPER],
      ],
    ],
    'Passing' => [
      'key_passes' => [
        'col_name' => ['total_att_assist',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 2.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 1.8
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'crossing' => [
        'col_name' => ['accurate_cross',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 1.75
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 0.95
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'long_passes' => [
        'col_name' => ['accurate_long_balls',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 4.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 3.0
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'free_kicks' => [
        'col_name' => ['att_freekick_total',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 4
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 2
          ]
        ],
        'standard' => PlayerStrengthStandardType::SUM
        // 'order_position' => [PlayerPosition::MIDFIELDER, PlayerPosition::DEFENDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
    ],
    'Defensive&Duels' => [
      'clearances' => [
        'col_name' => ['effective_clearance',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 5.0
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 3.5
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::DEFENDER,  PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'tackling' => [
        'col_name' => ['total_tackle',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 3.2
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 2.5
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::DEFENDER,  PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'aerial_duels' => [
        'col_name' => ['aerial_won',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 3.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 2.5
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::DEFENDER,  PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
      'interception' => [
        'col_name' => ['interception',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 1.8
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 1.3
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::DEFENDER,  PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER, PlayerPosition::GOALKEEPER],
      ],
    ],
    'Goalkeeping' => [
      'clean_sheet' => [
        'col_name' => ['clean_sheet',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 0.3
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 0.15
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::GOALKEEPER, PlayerPosition::DEFENDER, PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER],
      ],
      'saving' => [
        'col_name' => ['saves',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 4.0
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 2.5
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::GOALKEEPER, PlayerPosition::DEFENDER, PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER],
      ],
      'high_claim' => [
        'col_name' => ['total_high_claim',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 1.2
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 0.7
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::GOALKEEPER, PlayerPosition::DEFENDER, PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER],
      ],
      'saved_in_box' => [
        'col_name' => ['saved_ibox',],
        'cut_count' => [
          0 => [
            'type' => PlayerStrengthType::VERY_STRONG,
            'count' => 2.5
          ],
          1 => [
            'type' => PlayerStrengthType::STRONG,
            'count' => 1.5
          ]
        ],
        'standard' => PlayerStrengthStandardType::PER
        // 'order_position' => [PlayerPosition::GOALKEEPER, PlayerPosition::DEFENDER, PlayerPosition::MIDFIELDER, PlayerPosition::ATTACKER],
      ],
    ]
  ]
];

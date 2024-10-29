<?php

use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;

return [
  'event_series' => [
    SimulationEventType::SHOT => [SimulationEventType::CORNERKICK => [
      'attack' => [
        'pre_player' => [
          'add' => ['key_pass' => 1],
          'cal' => [],
        ],
        'player' => [
          'add' => ['shots' => 1, 'shots_on_target' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'keeper' => [
          'add' => ['save' => 1],
          'cal' => []
        ]
      ],
    ]],
    SimulationEventType::FOUL => [
      'defend' => [
        'player' => [
          'add' => ['foul' => 1],
          'cal' => []
        ]
      ],
    ],
    SimulationEventType::FOUL_FREE => ['none' => [
      'attack' => [
        'player' => [
          'add' => ['shots' => 1, 'shots_on_target' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1],
          'cal' => []
        ]
      ],
    ]],
    SimulationEventType::FOUL_Y_FREE => ['none' => [
      'attack' => [
        'player' => [
          'add' => ['shots' => 1, 'shots_on_target' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1, 'yellow_card' => 1],
          'cal' => []
        ]
      ],
    ]],
    SimulationEventType::FOUL_R_FREE => ['none' => [
      'attack' => [
        'player' => [
          'add' => ['shots' => 1, 'shots_on_target' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1, 'red_card' => 1],
          'cal' => []
        ]
      ],
    ]],
    SimulationEventType::FOUL_PK => [
      'attack' => [
        'player' => [
          'add' => ['get_pk' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1, 'pk_conceded' => 1],
          'cal' => []
        ]
      ],
    ],
    SimulationEventType::FOUL_Y_PK => [
      'attack' => [
        'player' => [
          'add' => ['get_pk' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1, 'yellow_card' => 1, 'pk_conceded' => 1],
          'cal' => []
        ]
      ],
    ],
    SimulationEventType::FOUL_R_PK => [
      'attack' => [
        'player' => [
          'add' => ['get_pk' => 1],
          'cal' => []
        ]
      ],
      'defend' => [
        'player' => [
          'add' => ['foul' => 1, 'red_card' => 1, 'pk_conceded' => 1],
          'cal' => []
        ]
      ],
    ],
  ],
  'event_ending' => [
    // event-ending
    SimulationEventType::SHOT => [
      SimulationEndingType::GOAL => [
        'attack' => [
          'pre_player' => [
            'add' => ['assist' => 1, 'key_pass' => 1],
            'cal' => []
          ],
          'player' => [
            'add' => ['shots' => 1, 'shots_on_target' => 1],
            'cal' => ['goal']
          ]
        ],
        'defend' => [
          'common' => [
            'add' => [],
            'cal' => ['conceded']
          ]
        ],
      ],
      SimulationEndingType::SAVED => [
        'attack' => [
          'pre_player' => [
            'add' => ['key_pass' => 1],
            'cal' => []
          ],
          'player' => [
            'add' => ['shots' => 1, 'shots_on_target' => 1],
            'cal' => []
          ]
        ],
        'defend' => [
          'keeper' => [
            'add' => ['save' => 1],
            'cal' => []
          ]
        ],
      ],
      SimulationEndingType::HITWOODWORK => [
        'attack' => [
          'pre_player' => [
            'add' => ['key_pass' => 1],
            'cal' => []
          ],
          'player' => [
            'add' => ['shots' => 1, 'hitwoodwork' => 1],
            'cal' => []
          ]
        ],
      ],
      SimulationEndingType::BLOCKED => [
        'attack' => [
          'pre_player' => [
            'add' => ['key_pass' => 1],
            'cal' => []
          ],
          'player' => [
            'add' => ['shots' => 1],
            'cal' => []
          ]
        ],
        'defend' => [
          'player' => [
            'add' => ['blocked'],
            'cal' => []
          ]
        ],
      ],
      SimulationEndingType::OUT => [
        'attack' => [
          'pre_player' => [
            'add' => ['key_pass' => 1],
            'cal' => []
          ],
          'player' => [
            'add' => ['shots' => 1],
            'cal' => []
          ]
        ],
      ],
    ],
    SimulationEventType::PK => [
      SimulationEndingType::GOAL => [
        'attack' => [
          'player' => [
            'add' => ['shots' => 1, 'shots_on_target' => 1],
            'cal' => ['goal']
          ]
        ],
        'defend' => [
          'common' => [
            'add' => [],
            'cal' => ['conceded']
          ]
        ],
      ],
      SimulationEndingType::SAVED => [
        'attack' => [
          'player' => [
            'add' => ['shots' => 1, 'shots_on_target' => 1, 'miss_pk' => 1],
            'cal' => []
          ]
        ],
        'defend' => [
          'keeper' => [
            'add' => ['save' => 1, 'save_pk' => 1],
            'cal' => []
          ]
        ],
      ],
      SimulationEndingType::HITWOODWORK => [
        'attack' => [
          'player' => [
            'add' => ['shots' => 1, 'miss_pk' => 1],
            'cal' => []
          ]
        ],
      ],
      SimulationEndingType::OUT => [
        'attack' => [
          'player' => [
            'add' => ['shots' => 1, 'miss_pk' => 1],
            'cal' => []
          ]
        ],
      ],
    ],
    // event-none-ending
    /**ending 관련 처리만 */
    'none' => [
      SimulationEndingType::GOAL => [
        'attack' => [
          'player' => [
            'add' => [],
            'cal' => ['goal']
          ]
        ],
        'defend' => [
          'common' => [
            'add' => [],
            'cal' => ['conceded'],
          ]
        ],
      ],
      SimulationEndingType::SAVED => [
        'defend' => [
          'keeper' => [
            'add' => ['save' => 1],
            'cal' => [],
          ]
        ],
      ],
      SimulationEndingType::HITWOODWORK => [
        'defend' => [],
      ],
      SimulationEndingType::OUT => [
        'defend' => [],
      ],
      SimulationEndingType::BLOCKED => [
        'defend' => [
          'player' => [
            'add' => ['blocked'],
            'cal' => []
          ]
        ],
      ],
    ],
  ]
];

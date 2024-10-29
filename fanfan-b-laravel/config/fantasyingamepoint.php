<?php

use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\StatCategory;

return [
  'FANTASYINGAMEPOINT_REFERENCE_TABLE_V0' => [
    'MomTable' => [
      'mom_yes' => [
        1 => 1.5,
        2 => 3.2,
        3 => 5,
        4 => 7,
        5 => 9.5,
        6 => 12.4,
        7 => 16,
        8 => 20.3,
        9 => 25.6,
      ],
      'mom_no' => [
        1 => 1.5,
        2 => 3,
        3 => 4.5,
        4 => 6.0,
        5 => 7.8,
        6 => 9.9,
        7 => 12.5,
        8 => 15.7,
        9 => 19.7,
      ],
    ],
    'GradeWeightRate' => [
      CardGrade::GOAT => 8.0,
      CardGrade::FANTASY => 6.0,
      CardGrade::ELITE => 4.0,
      CardGrade::AMAZING => 2.0,
      CardGrade::DECENT => 1.0,
      CardGrade::NORMAL => 0.0
    ],
    'LevelCate' => [
      'attacking_level',
      'goalkeeping_level',
      'passing_level',
      'defensive_level',
      'duel_level',
    ],
    'Projection' => [
      FantasyPointCategoryType::OFFENSIVE => [
        'category' => [
          9 => 5.0,
          8 => 4.5,
          7 => 4.0,
          6 => 3.5,
          5 => 3.0,
          4 => 1.5,
          3 => 1.0,
          2 => 0.7,
          1 => 0.2,
        ],
        'special_skill' => 2.3
      ],
      FantasyPointCategoryType::PASSING => [
        'category' => [
          9 => 4.5,
          8 => 4.0,
          7 => 3.5,
          6 => 3.0,
          5 => 2.5,
          4 => 1.5,
          3 => 1.0,
          2 => 0.7,
          1 => 0.2,
        ],
        'special_skill' => 1.5
      ],
      FantasyPointCategoryType::DEFENSIVE => [
        'category' => [
          9 => 4.5,
          8 => 4.0,
          7 => 3.0,
          6 => 2.5,
          5 => 2.0,
          4 => 1.5,
          3 => 1.0,
          2 => 0.7,
          1 => 0.2,
        ],
        'special_skill' => 1.5
      ],
      FantasyPointCategoryType::DUEL => [
        'category' => [
          9 => 5.0,
          8 => 4.5,
          7 => 3.5,
          6 => 3.0,
          5 => 2.5,
          4 => 1.5,
          3 => 1.0,
          2 => 0.7,
          1 => 0.2,
        ],
        'special_skill' => 1.5
      ],
      FantasyPointCategoryType::GOALKEEPING => [
        'category' => [
          9 => 4.0,
          8 => 3.0,
          7 => 2.0,
          6 => 1.5,
          5 => 1.0,
          4 => 1.0,
          3 => 0.7,
          2 => 0.2,
          1 => 0.2,
        ],
        'special_skill' => 1.2
      ],
    ],
    'AdditionalPoint' => [
      FantasyPointCategoryType::OFFENSIVE => [
        'category' => [
          'column' => StatCategory::ATTACKING,
          'range' => [
            20 => [
              9 => 5.5,
              8 => 5,
              7 => 4.5,
              6 => 4,
              5 => 3.5,
              4 => 2,
              3 => 1.5,
              2 => 1,
              1 => 0.5,
            ],
            14 => [
              9 => 5,
              8 => 4.5,
              7 => 4,
              6 => 3.5,
              5 => 3.0,
              4 => 1.5,
              3 => 1,
              2 => 0.5,
              1 => 0,
            ],
            -9999 => [
              9 => 4.5,
              8 => 4,
              7 => 3.5,
              6 => 3,
              5 => 2.5,
              4 => 1.0,
              3 => 0.5,
              2 => 0.5,
              1 => 0,
            ]
          ]
        ],
        'specialSkill' => [
          'stat' => ['goals'],
          'range' => [
            3 => 3.5,
            2 => 2.5,
            1 => 1
          ]
        ]
      ],
      FantasyPointCategoryType::PASSING => [
        'category' => [
          'column' => StatCategory::PASSING,
          'range' => [
            24 => [
              9 => 5,
              8 => 4.5,
              7 => 4,
              6 => 3.5,
              5 => 3,
              4 => 2,
              3 => 1.5,
              2 => 1,
              1 => 0.5,
            ],
            15 => [
              9 => 4.5,
              8 => 4,
              7 => 3.5,
              6 => 3,
              5 => 2.5,
              4 => 1.5,
              3 => 1,
              2 => 0.5,
              1 => 0,
            ],
            -9999 => [
              9 => 4,
              8 => 3.5,
              7 => 3,
              6 => 2.5,
              5 => 2,
              4 => 1,
              3 => 0.5,
              2 => 0.5,
              1 => 0,
            ]
          ]
        ],
        'specialSkill' => [
          'stat' => ['accuratePass', 'totalPass'],
          'range' => [
            95 => 2.5,
            90 => 1.5,
            85 => 0.5
          ]
        ]
      ],
      FantasyPointCategoryType::DEFENSIVE => [
        'category' => [
          'column' => StatCategory::DEFENSIVE,
          'range' => [
            14 => [
              9 => 5,
              8 => 4.5,
              7 => 3.5,
              6 => 3,
              5 => 2.5,
              4 => 2,
              3 => 1.5,
              2 => 1,
              1 => 0.5,
            ],
            9 => [
              9 => 4.5,
              8 => 4,
              7 => 3,
              6 => 2.5,
              5 => 2,
              4 => 1.5,
              3 => 1,
              2 => 0.5,
              1 => 0,
            ],
            -9999 => [
              9 => 4,
              8 => 3.5,
              7 => 2.5,
              6 => 2,
              5 => 1.5,
              4 => 1,
              3 => 0.5,
              2 => 0.5,
              1 => 0,
            ]
          ]
        ],
        'specialSkill' => [
          'stat' => ['effectiveClearance'],
          'range' => [
            8 => 2.5,
            6 => 1.5,
            4 => 0.5
          ]
        ]
      ],
      FantasyPointCategoryType::DUEL => [
        'category' => [
          'column' => FantasyPointCategoryType::DUEL,
          'range' => [
            21 => [
              9 => 5.5,
              8 => 5,
              7 => 4,
              6 => 3.5,
              5 => 3,
              4 => 2,
              3 => 1.5,
              2 => 1,
              1 => 0.5,
            ],
            15 => [
              9 => 5,
              8 => 4.5,
              7 => 3.5,
              6 => 3,
              5 => 2.5,
              4 => 1.5,
              3 => 1,
              2 => 0.5,
              1 => 0,
            ],
            -9999 => [
              9 => 4.5,
              8 => 4,
              7 => 3,
              6 => 2.5,
              5 => 2,
              4 => 1,
              3 => 0.5,
              2 => 0.5,
              1 => 0,
            ]
          ]
        ],
        'specialSkill' => [
          'stat' => ['duelWon'],
          'range' => [
            10 => 2.5,
            8 => 1.5,
            6 => 0.5
          ]
        ]
      ],
      FantasyPointCategoryType::GOALKEEPING => [
        'category' => [
          'column' => StatCategory::GOALKEEPING,
          'range' => [
            27 => [
              9 => 4.5,
              8 => 3.5,
              7 => 2.5,
              6 => 2,
              5 => 1.5,
              4 => 1.5,
              3 => 1,
              2 => 0.5,
              1 => 0.5,
            ],
            20 => [
              9 => 4,
              8 => 3,
              7 => 2,
              6 => 1.5,
              5 => 1,
              4 => 1,
              3 => 0.5,
              2 => 0,
              1 => 0,
            ],
            -9999 => [
              9 => 3.5,
              8 => 2.5,
              7 => 1.5,
              6 => 1,
              5 => 0.5,
              4 => 0.5,
              3 => 0.5,
              2 => 0,
              1 => 0,
            ]
          ]
        ],
        'specialSkill' => [
          'stat' => ['saves'],
          'range' => [
            8 => 2,
            6 => 1,
            4 => 0.5
          ]
        ]
      ],
    ]
  ]
];

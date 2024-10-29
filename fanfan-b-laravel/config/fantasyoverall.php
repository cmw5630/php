<?php

use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\SimulationCalculator\SimulationCategoryType;

return [
  'column' => [
    'shot' => FantasyDraftCategoryType::ATTACKING,
    'finishing' => FantasyDraftCategoryType::ATTACKING,
    'dribbles' => FantasyDraftCategoryType::ATTACKING,
    'positioning' => FantasyDraftCategoryType::ATTACKING,
    'passing' => FantasyDraftCategoryType::PASSING,
    'chance_create' => FantasyDraftCategoryType::PASSING,
    'long_pass' => FantasyDraftCategoryType::PASSING,
    'crosses' => FantasyDraftCategoryType::PASSING,
    'tackles' => FantasyDraftCategoryType::DEFENSIVE,
    'blocks' => FantasyDraftCategoryType::DEFENSIVE,
    'clearances' => FantasyDraftCategoryType::DEFENSIVE,
    'instinct' => FantasyDraftCategoryType::DEFENSIVE,
    'ground_duels' => FantasyDraftCategoryType::DUEL,
    'aerial_duels' => FantasyDraftCategoryType::DUEL,
    'interceptions' => FantasyDraftCategoryType::DUEL,
    'recoveries' => FantasyDraftCategoryType::DUEL,
    'saves' => FantasyDraftCategoryType::GOALKEEPING,
    'high_claims' => FantasyDraftCategoryType::GOALKEEPING,
    'sweeper' => FantasyDraftCategoryType::GOALKEEPING,
    'punches' => FantasyDraftCategoryType::GOALKEEPING,
    'speed' => SimulationCategoryType::PHYSICAL,
    'balance' => SimulationCategoryType::PHYSICAL,
    'power' => SimulationCategoryType::PHYSICAL,
    'stamina' => SimulationCategoryType::PHYSICAL
  ],
  'additional' => [
    'grade' => [
      CardGrade::GOAT => 3,
      CardGrade::FANTASY => 2,
      CardGrade::ELITE => 1,
      CardGrade::AMAZING => 1,
      CardGrade::DECENT => 0,
      CardGrade::NORMAL => 0
    ],
    'mom' => [
      true => 2,
      false => 0
    ],
    'category' => [
      9 => 6,
      8 => 5,
      7 => 4,
      6 => 3,
      5 => 3,
      4 => 2,
      3 => 1,
      2 => 1,
      1 => 0,
      0 => 0
    ],
    'special_skill' => [
      'stat' => [
        'assists' => ['chance_create'],
        'winning_goal' => ['positioning'],
        'shots_on_target' => ['shot'],
        'successful_dribbles' => ['dribbles'],
        'goals' => ['finishing'],
        'accurate_crosses' => ['crosses'],
        'passes_into_final_third' => ['passing'],
        'key_passes' => ['chance_create'],
        'accurate_long_passes' => ['long_pass'],
        'pass_accuracy' => ['passing'],
        'offside_provoked' =>  ['instinct'],
        'clean_sheet' => ['instinct'],
        'tackles_won' => ['tackles'],
        'blocks' => ['blocks'],
        'clearances' => ['clearances'],
        'aerial_duel_won' => ['aerial_duels'],
        'ground_duels_won' => ['ground_duels'],
        'recoveries' => ['recoveries'],
        'interceptions' => ['interceptions'],
        'duels_won' => ['aerial_duels', 'ground_duels'],
        'acted_as_sweeper' => ['sweeper'],
        'saved_in_box' => ['saves'],
        'punches' => ['punches'],
        'high_claims' => ['high_claims'],
        'saves' => ['saves'],
      ],
      'point' => [
        3 => 3,
        2 => 2,
        1 => 1
      ],
    ],
    // 'rating' => [
    //   '8.5' => 2,
    //   '7.5' => 1,
    //   '6' => 0,
    //   '5.0' => -1,
    //   '0' => -2
    // ]
  ],
  'final' => [
    PlayerSubPosition::ST => [
      'shot' => 0.2,
      'finishing' => 0.25,
      'positioning' => 0.21,
      'dribbles' => 0.09,
      'aerial_duels' => 0.08,
      'speed' => 0.05,
      'balance' => 0.09,
      'power' => 0.03
    ],
    PlayerSubPosition::LW => [
      'shot' => 0.08,
      'finishing' => 0.12,
      'positioning' => 0.12,
      'dribbles' => 0.25,
      'chance_create' => 0.12,
      'crosses' => 0.05,
      'speed' => 0.05,
      'balance' => 0.21,
    ],
    PlayerSubPosition::RW => [
      'shot' => 0.08,
      'finishing' => 0.12,
      'positioning' => 0.12,
      'dribbles' => 0.25,
      'chance_create' => 0.12,
      'crosses' => 0.05,
      'speed' => 0.05,
      'balance' => 0.21,
    ],
    PlayerSubPosition::LM => [
      'finishing' => 0.05,
      'positioning' => 0.08,
      'dribbles' => 0.25,
      'chance_create' => 0.15,
      'crosses' => 0.13,
      'speed' => 0.08,
      'balance' => 0.21,
      'stamina' => 0.05
    ],
    PlayerSubPosition::RM => [
      'finishing' => 0.05,
      'positioning' => 0.08,
      'dribbles' => 0.25,
      'chance_create' => 0.15,
      'crosses' => 0.13,
      'speed' => 0.08,
      'balance' => 0.21,
      'stamina' => 0.05
    ],
    PlayerSubPosition::AM => [
      'shot' => 0.14,
      'finishing' => 0.03,
      'positioning' => 0.14,
      'dribbles' => 0.1,
      'passing' => 0.1,
      'chance_create' => 0.37,
      'ground_duels' => 0.07,
      'balance' => 0.05,
    ],
    PlayerSubPosition::CM => [
      'passing' => 0.24,
      'chance_create' => 0.08,
      'long_pass' => 0.1,
      'tackles' => 0.14,
      'ground_duels' => 0.1,
      'interceptions' => 0.14,
      'recoveries' => 0.1,
      'stamina' => 0.1,
    ],
    PlayerSubPosition::DM => [
      'passing' => 0.21,
      'long_pass' => 0.15,
      'recoveries' => 0.15,
      'tackles' => 0.15,
      'interceptions' => 0.1,
      'instinct' => 0.1,
      'ground_duels' => 0.08,
      'stamina' => 0.06,
    ],
    PlayerSubPosition::CB => [
      'passing' => 0.06,
      'tackles' => 0.15,
      'blocks' => 0.15,
      'clearances' => 0.15,
      'instinct' => 0.24,
      'aerial_duels' => 0.09,
      'interceptions' => 0.1,
      'power' => 0.06
    ],
    PlayerSubPosition::LB => [
      'dribbles' => 0.08,
      'chance_create' => 0.08,
      'crosses' => 0.18,
      'tackles' => 0.18,
      'instinct' => 0.05,
      'interceptions' => 0.23,
      'speed' => 0.15,
      'stamina' => 0.05,
    ],
    // PlayerSubPosition::LWB => [
    //   'dribbles' => 0.08,
    //   'chance_create' => 0.08,
    //   'crosses' => 0.18,
    //   'tackles' => 0.18,
    //   'instinct' => 0.05,
    //   'interceptions' => 0.23,
    //   'speed' => 0.15,
    //   'stamina' => 0.05,
    // ],
    PlayerSubPosition::RB => [
      'dribbles' => 0.08,
      'chance_create' => 0.08,
      'crosses' => 0.18,
      'tackles' => 0.18,
      'instinct' => 0.05,
      'interceptions' => 0.23,
      'speed' => 0.15,
      'stamina' => 0.05,
    ],
    // PlayerSubPosition::RWB => [
    //   'dribbles' => 0.08,
    //   'chance_create' => 0.08,
    //   'crosses' => 0.18,
    //   'tackles' => 0.18,
    //   'instinct' => 0.05,
    //   'interceptions' => 0.23,
    //   'speed' => 0.15,
    //   'stamina' => 0.05,
    // ],
    PlayerSubPosition::GK => [
      'passing' => 0.04,
      'long_pass' => 0.03,
      'saves' => 0.35,
      'high_claims' => 0.17,
      'sweeper' => 0.17,
      'punches' => 0.17,
      'instinct' => 0.04,
      'recoveries' => 0.03
    ],
  ],
  'fluctuation' => [
    'match' => [
      'min_mins' => 26,
      'grades' => [
        // [등 기준, 락 기준]
        OriginGrade::SS => [
          'rating' => [8.6, 6.2],
          'power_ranking' => [35.1, 1.3]
        ],
        OriginGrade::S => [
          'rating' => [8.1, 6.1],
          'power_ranking' => [29.2, -0.5]
        ],
        OriginGrade::A => [
          'rating' => [7.9, 6],
          'power_ranking' => [25.3, -1.5]
        ],
        OriginGrade::B => [
          'rating' => [7.7, 5.9],
          'power_ranking' => [21.6, -2.4]
        ],
        OriginGrade::C => [
          'rating' => [7.5, 5.8],
          'power_ranking' => [19.1, -4.1]
        ],
        OriginGrade::D => [
          'rating' => [7.2, 5.6],
          'power_ranking' => [13.8, -9.1]
        ]
      ]
    ],
    'grade' => [
      // 등급변동 시 [+점수]
      OriginGrade::SS => [
        'power_ranking' => [[38.7, 1]],
        'minus' => -2,
      ],
      OriginGrade::S => [
        'power_ranking' => [[0, 2]],
        'minus' => -3,
      ],
      OriginGrade::A => [
        'power_ranking' => [
          [24.2, 4],
          [20.9, 3],
          [0, 2]
        ],
        'minus' => -4,
      ],
      OriginGrade::B => [
        'power_ranking' => [
          [20.7, 5],
          [17.9, 4],
          [0, 3]
        ],
        'minus' => -4,
      ],
      OriginGrade::C => [
        'power_ranking' => [
          [18.5, 5],
          [15.9, 4],
          [0, 3]
        ],
        'minus' => -5,
      ],
      OriginGrade::D => [
        'power_ranking' => [
          [13.2, 6],
          [11.0, 5],
          [0, 4]
        ],

        'minus' => 0,
      ]
    ]
  ],
  'sub_position' => [
    'sub_position' => 0,
    'second_position' => -10,
    'third_position' => -15
  ],
  'overall_penalty' => [
    '80' => -13,
    '75' => -9,
    '70' => -7,
    '65' => -3,
    '60' => -2,
    '55' => -1,
  ]
];

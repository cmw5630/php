<?php

use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\FantasyCalculator\FantasyPolicyType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;

$commonPositionTable = [
  'common' =>  [
    // Attacking
    // 속성이름, // 표시정보 (툴팁)
    'goal_assist' => [0 => 0], // Assists
    'winning_goal', // Winning Goal
    'total_scoring_att', // Shots on target
    'won_contest', // Successful Dribbles
    'goals', // Goals
    // Passing
    'accurate_cross', // Accurate Crosses
    'final_third_entries', // Passes into Final Third,
    'total_att_assist',  // Key Passes
    'accurate_long_balls', // Accurate Long Passes
    'accurate_pass/total_pass', // Pass Accuracy
    // Defensive
    'offside_provoked', // Offside Provoked
    'clean_sheet', // Clean Sheet
    'won_tackle', // Tackles Won
    'outfielder_block', // Blocks
    'effective_clearance', // Clearances
    // Duel
    'aerial_won', // Aerial Duels Won
    'duel_won-aerial_won', // Ground Duels Won
    'ball_recovery', // Recoveries
    'interception', // Interceptions
    'duel_won', // Duels Won
    // Goalkeeping
    'accurate_keeper_sweeper', // Acted as Sweeper
    'saved_ibox', // Saved in Box
    'punches', // Punches
    'good_high_claim', // High Claims 
    'saves', // Saves
  ],
];

return [
  'FANTASYDRAFT_REFERENCE_TABLE_V0' => [
    'Policy' => [
      // 'goals' => [
      //   'type' => FantasyPolicyType::QUANTILE,
      //   'weight' => false,
      // ],
      'accurate_pass/total_pass' => [
        'type' => FantasyPolicyType::QUANTILE_MIN_VALUE,
        'weight' => false,
        'minValueCombName' => 'total_pass',
        'minValueRef' => [
          'position' => [
            PlayerDailyPosition::STRIKER => 20,
            PlayerDailyPosition::ATTACKING_MIDFIELDER => 40,
            PlayerDailyPosition::MIDFIELDER => 40,
            PlayerDailyPosition::DEFENSIVE_MIDFIELDER => 40,
            PlayerDailyPosition::DEFENDER => 50,
            PlayerDailyPosition::WING_BACK => 50,
            PlayerDailyPosition::GOALKEEPER => 20,
          ],
          'sub_position' => [
            PlayerPosition::ATTACKER => 20,
            PlayerPosition::MIDFIELDER => 40,
            PlayerPosition::DEFENDER => 50,
            PlayerPosition::GOALKEEPER => 20,
          ]
        ]
      ],
      // 'mins_played' => [
      //   'type' => FantasyPolicyType::QUANTILE,
      //   'weight' => false,
      // ],
      // 'example' => [
      //   'type' => FantasyPolicyType::QUANTILE,
      //   'weight' => false,
      //   // 'order' => OrderType::DESC,
      //   // 'compare' => CompareType::GTE,
      // ],
    ],
    'CombTable' => [
      'position' => [
        PlayerDailyPosition::STRIKER => $commonPositionTable['common'],
        PlayerDailyPosition::ATTACKING_MIDFIELDER => $commonPositionTable['common'],
        PlayerDailyPosition::MIDFIELDER => $commonPositionTable['common'],
        PlayerDailyPosition::DEFENSIVE_MIDFIELDER => $commonPositionTable['common'],
        PlayerDailyPosition::WING_BACK => $commonPositionTable['common'],
        PlayerDailyPosition::DEFENDER => $commonPositionTable['common'],
        PlayerPosition::GOALKEEPER => $commonPositionTable['common'],
      ],
      'sub_position' => [
        PlayerPosition::ATTACKER => $commonPositionTable['common'],
        PlayerPosition::MIDFIELDER => $commonPositionTable['common'],
        PlayerPosition::DEFENDER => $commonPositionTable['common'],
        PlayerPosition::GOALKEEPER => $commonPositionTable['common'],
      ]
    ],
    // 'Categories' => [
    //   FantasyDraftCategoryType::ATTACKING => [
    //     // 속성이름(hover), // 표시정보 (툴팁)
    //     'assists' => 'goal_assist', // Assists
    //     'winning_goal' => 'winning_goal', // Winning Goal
    //     'shots_on_target' => 'ontarget_scoring_att', // Shots on target
    //     'successful_dribbles' => 'won_contest', // Successful Dribbles
    //     'goals' => 'goals', // Goals
    //   ],
    //   FantasyDraftCategoryType::PASSING => [
    //     'accurate_crosses' => 'accurate_cross', // Accurate Crosses
    //     'passes_into_final_third' => 'final_third_entries', // Passes into Final Third,
    //     'key_passes' => 'total_att_assist',  // Key Passes
    //     'accurate_long_passes' => 'accurate_long_balls', // Accurate Long Passes
    //     'pass_accuracy' => 'accurate_pass/total_pass', // Pass Accuracy
    //   ],
    //   FantasyDraftCategoryType::DEFENSIVE => [
    //     'offside_provoked' => 'offside_provoked', // Offside Provoked
    //     'clean_sheet' => 'clean_sheet', // Clean Sheet
    //     'tackles_won' => 'won_tackle', // Tackles Won
    //     'blocks' => 'outfielder_block', // Blocks
    //     'clearances' => 'effective_clearance', // Clearances
    //   ],
    //   FantasyDraftCategoryType::DUEL => [
    //     'aerial_duels_won' => 'aerial_won', // Aerial Duels Won
    //     'ground_duels_won' => 'duel_won-aerial_won', // Ground Duels Won
    //     'recoveries' => 'ball_recovery', // Recoveries
    //     'interceptions' => 'interception', // Interceptions
    //     'duels_won' => 'duel_won', // Duels Won
    //   ],
    //   FantasyDraftCategoryType::GOALKEEPING => [
    //     'acted_as_sweeper' => 'accurate_keeper_sweeper', // Acted as Sweeper
    //     'saved_in_box' => 'saved_ibox', // Saved in Box
    //     'punches' => 'punches', // Punches
    //     'high_claims' => 'good_high_claim', // High Claims 
    //     'saves' => 'saves', // Saves
    //   ],
    // ],
    'DraftPolicy' => [
      'max' => ['cost' => 9, 'level' => 9],
      'price' => [
        'price_grade' => null,
        'type' => 'gold',
        'table' => [],
      ]
    ],
    'SpecialSkills' => ['goals', 'pass_accuracy', 'clearances', 'duels_won', 'saves'],
    'Categories' => [
      FantasyDraftCategoryType::ATTACKING => [
        // 속성이름(hover), // 표시정보 (툴팁)
        'assists' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 1], 'display' => [1 => '1+']],
          'column' => 'goal_assist',
          'hover' => 'Assists',
        ], // Assists
        'winning_goal' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 1], 'display' => [1 => '1']],
          'column' => 'winning_goal',
          'hover' => 'Winning Goal',
        ], // Winning Goal
        'shots_on_target' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 3, 2 => 5], 'display' => [1 => '3~4', 2 => '5+']],
          'column' => 'total_scoring_att',
          'hover' => 'Shots on target',
        ], // Shots on target
        'successful_dribbles' => [
          'cost' => 2,
          'levelMap' => ['value' =>  [1 => 1, 2 => 2], 'display' => [1 => '1', 2 => '2+']],
          'column' => 'won_contest',
          'hover' => 'Successful Dribbles',
        ], // Successful Dribbles
        'goals' => [
          'cost' => 3,
          'levelMap' => ['value' => [1 => 1, 2 => 2, 3 => 3], 'display' => [1 => '1', 2 => '2', 3 => '3+']],
          'column' => 'goals',
          'hover' => 'Goals',
        ], // Goals
      ],
      FantasyDraftCategoryType::GOALKEEPING => [
        'acted_as_sweeper' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 1], 'display' => [1 => '1+']],
          'column' => 'accurate_keeper_sweeper',
          'hover' => 'Acted as Sweeper',
        ], // Acted as Sweeper
        'saved_in_box' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 3], 'display' => [1 => '3+']],
          'column' => 'saved_ibox',
          'hover' => 'Saved in Box',
        ], // Saved in Box
        'punches' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 1, 2 => 2], 'display' => [1 => '1', 2 => '2+']],
          'column' => 'punches',
          'hover' => 'Punches',
        ], // Punches
        'high_claims' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 2, 2 => 3], 'display' => [1 => '2', 2 => '3+']],
          'column' => 'good_high_claim',
          'hover' => 'High Claims',
        ], // High Claims 
        'saves' => [
          'cost' => 3,
          'levelMap' => ['value' => [1 => 4, 2 => 6, 3 => 8], 'display' => [1 => '4~5', 2 => '6~7', 3 => '8+']],
          'column' => 'saves',
          'hover' => 'Saves',
        ], // Saves
      ],
      FantasyDraftCategoryType::PASSING => [
        'accurate_crosses' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 2], 'display' => [1 => '2+']],
          'column' => 'accurate_cross',
          'hover' => 'Accurate Crosses',
        ], // Accurate Crosses
        'passes_into_final_third' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 10], 'display' => [1 => '10+']],
          'column' => 'final_third_entries',
          'hover' => 'Passes into Final Third',
        ], // Passes into Final Third,
        'key_passes' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 2, 2 => 3], 'display' => [1 => '2', 2 => '3+']],
          'column' => 'total_att_assist',
          'hover' => 'Key Passes',
        ],  // Key Passes
        'accurate_long_passes' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 5, 2 => 8], 'display' => [1 => '5~7', 2 => '8+']],
          'column' => 'accurate_long_balls',
          'hover' => 'Accurate Long Passes',
        ], // Accurate Long Passes
        'pass_accuracy' => [
          'cost' => 3,
          'levelMap' => ['value' => [1 => 0.845, 2 => 0.895, 3 => 0.945], 'display' => [1 => '85~89', 2 => '90~94', 3 => '95+']],
          'column' => 'accurate_pass/total_pass',
          'hover' => 'Pass Accuracy',
        ], // Pass Accuracy
      ],
      FantasyDraftCategoryType::DEFENSIVE => [
        'offside_provoked' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 1], 'display' => [1 => '1+']],
          'column' => 'offside_provoked',
          'hover' => 'Offside Provoked',
        ], // Offside Provoked
        'clean_sheet' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 1], 'display' => [1 => '1']],
          'column' => 'clean_sheet',
          'hover' => 'Clean Sheet',
        ], // Clean Sheet
        'tackles_won' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 2, 2 => 3], 'display' => [1 => '2', 2 => '3+']],
          'column' => 'won_tackle',
          'hover' => 'Tackles Won',
        ], // Tackles Won
        'blocks' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 1, 2 => 2], 'display' => [1 => '1', 2 => '2+']],
          'column' => 'outfielder_block',
          'hover' => 'Blocks',
        ], // Blocks
        'clearances' => [
          'cost' => 3,
          'levelMap' => ['value' => [1 => 4, 2 => 6, 3 => 8], 'display' => [1 => '4~5', 2 => '6~7', 3 => '8+']],
          'column' => 'effective_clearance',
          'hover' => 'Clearances',
        ], // Clearances
      ],
      FantasyDraftCategoryType::DUEL => [
        'aerial_duel_won' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 4], 'display' => [1 => '4+']],
          'column' => 'aerial_won',
          'hover' => 'Aerial Duels Won',
        ], // Aerial Duels Won
        'ground_duels_won' => [
          'cost' => 1,
          'levelMap' => ['value' => [1 => 6], 'display' => [1 => '6+']],
          'column' => 'duel_won-aerial_won',
          'hover' => 'Ground Duels Won',
        ], // Ground Duels Won
        'recoveries' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 8, 2 => 11], 'display' => [1 => '8~10', 2 => '11+']],
          'column' => 'ball_recovery',
          'hover' => 'Recoveries',
        ], // Recoveries
        'interceptions' => [
          'cost' => 2,
          'levelMap' => ['value' => [1 => 2, 2 => 4], 'display' => [1 => '2~3', 2 => '4+']],
          'column' => 'interception',
          'hover' => 'Interceptions',
        ], // Interceptions
        'duels_won' => [
          'cost' => 3,
          'levelMap' => ['value' => [1 => 6, 2 => 8, 3 => 10], 'display' => [1 => '6~7', 2 => '8~9', 3 => '10+']],
          'column' => 'duel_won',
          'hover' => 'Duels Won',
        ], // Duels Won
      ],
    ]
  ]
];

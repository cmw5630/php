<?php

use App\Enums\FantasyCalculator\FantasyPolicyType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;

$commonPositionTable = [
  PlayerPosition::ATTACKER =>  [
    // 공격
    'goals' => ['0' => 0, '1' => 10, '2' => 20, '3' => 40, '4' => 50],
    'winning_goal' => 3,
    'att_freekick_goal' => 2,
    'ontarget_scoring_att' => 1.5,
    'goal_assist' => 8,
    'won_contest' => 1,
    'big_chance_missed' => -1,
    'att_pen_miss+att_pen_post+att_pen_target' => -3,
    'total_offside' => -0.3,
    // 패스
    'total_att_assist' => 2,
    'big_chance_created' => 3,
    'final_third_entries' => 0.1,
    'accurate_cross' => 1,
    'accurate_long_balls' => 0.5,
    'accurate_pass/total_pass' => ['0' => 0, '0.845' => 4, '0.895' => 5, '0.945' => 6],
    'total_pass-accurate_pass' => -0.1,
    // 수비
    'won_tackle' => 1.2,
    'outfielder_block' => 1.4,
    'effective_clearance' => 0.8,
    'offside_provoked' => 1.4,
    'fouls' => -0.2,
    'penalty_conceded' => -5,
    'clean_sheet' => 5,
    'goals_conceded' => 0,
    'error_lead_to_shot' => -1.5,
    'error_lead_to_goal' => -6,
    // 경합
    'ball_recovery' => 0.8,
    'interception' => 1,
    'penalty_won' => 5,
    'duel_won' => 1,
    'duel_lost' => -0.2,
    'duel_won-aerial_won' => 0.4,
    'duel_lost-aerial_lost' => -0.2,
    'aerial_won' => 0.6,
    'aerial_lost' => -0.2,
    // 골키핑
    'saves' => 0,
    'saved_ibox' => 0,
    'penalty_save' => 0,
    'good_high_claim' => 0,
    'dive_catch' => 0,
    'punches' => 0,
    'accurate_keeper_sweeper' => 0,
    // 일반
    'mins_played' => ['0' => 0, '1' => 0.2, '15' => 0.5, '46' => 0.8, '90' => 1],
    'own_goals' => -6,
    'yellow_card' => -1,
    'red_card' => -3,
  ],
  PlayerPosition::MIDFIELDER =>  [
    'goals' => ['0' => 0, '1' => 10, '2' => 20, '3' => 40, '4' => 50],
    'winning_goal' => 3,
    'att_freekick_goal' => 2,
    'ontarget_scoring_att' => 1.5,
    'goal_assist' => 8,
    'won_contest' => 1,
    'big_chance_missed' => -1,
    'att_pen_miss+att_pen_post+att_pen_target' => -3,
    'total_offside' => -0.3,
    'total_att_assist' => 2,
    'big_chance_created' => 3,
    'final_third_entries' => 0.1,
    'accurate_cross' => 1,
    'accurate_long_balls' => 0.5,
    'accurate_pass/total_pass' => ['0' => 0, '0.845' => 3, '0.895' => 4, '0.945' => 5],
    'total_pass-accurate_pass' => -0.2,
    'won_tackle' => 1.2,
    'outfielder_block' => 1.4,
    'effective_clearance' => 0.8,
    'offside_provoked' => 1.5,
    'fouls' => -0.2,
    'penalty_conceded' => -5,
    'clean_sheet' => 5,
    'goals_conceded' => -0.5,
    'error_lead_to_shot' => -1.5,
    'error_lead_to_goal' => -6,
    'ball_recovery' => 0.8,
    'interception' => 1,
    'penalty_won' => 5,
    'duel_won' => 1,
    'duel_lost' => -0.3,
    'duel_won-aerial_won' => 0.4,
    'duel_lost-aerial_lost' => -0.2,
    'aerial_won' => 0.6,
    'aerial_lost' => -0.2,
    'saves' => 0,
    'saved_ibox' => 0,
    'penalty_save' => 0,
    'good_high_claim' => 0,
    'dive_catch' => 0,
    'punches' => 0,
    'accurate_keeper_sweeper' => 0,
    'mins_played' => ['0' => 0, '1' => 0.2, '15' => 0.5, '46' => 0.8, '90' => 1],
    'own_goals' => -6,
    'yellow_card' => -1,
    'red_card' => -3,
  ],
  PlayerPosition::DEFENDER =>  [
    'goals' => ['0' => 0, '1' => 12, '2' => 24, '3' => 48, '4' => 60],
    'winning_goal' => 3,
    'att_freekick_goal' => 2,
    'ontarget_scoring_att' => 1.8,
    'goal_assist' => 8,
    'won_contest' => 1,
    'big_chance_missed' => -1,
    'att_pen_miss+att_pen_post+att_pen_target' => -3,
    'total_offside' => -0.3,
    'total_att_assist' => 2.3,
    'big_chance_created' => 3,
    'final_third_entries' => 0.1,
    'accurate_cross' => 1,
    'accurate_long_balls' => 0.5,
    'accurate_pass/total_pass' => ['0' => 0, '0.845' => 1.5, '0.895' => 2.5, '0.945' => 3.5],
    'total_pass-accurate_pass' => -0.3,
    'won_tackle' => 1.4,
    'outfielder_block' => 1.6,
    'effective_clearance' => 1,
    'offside_provoked' => 1.6,
    'fouls' => -0.6,
    'penalty_conceded' => -5,
    'clean_sheet' => 5.5,
    'goals_conceded' => -2,
    'error_lead_to_shot' => -2,
    'error_lead_to_goal' => -6,
    'ball_recovery' => 0.8,
    'interception' => 1.2,
    'penalty_won' => 5,
    'duel_won' => 1,
    'duel_lost' => -0.5,
    'duel_won-aerial_won' => 0.5,
    'duel_lost-aerial_lost' => -0.3,
    'aerial_won' => 0.7,
    'aerial_lost' => -0.4,
    'saves' => 0,
    'saved_ibox' => 0,
    'penalty_save' => 0,
    'good_high_claim' => 0,
    'dive_catch' => 0,
    'punches' => 0,
    'accurate_keeper_sweeper' => 0,
    'mins_played' => ['0' => 0, '1' => 0.2, '15' => 0.5, '46' => 0.8, '90' => 1],
    'own_goals' => -6,
    'yellow_card' => -2,
    'red_card' => -5,
  ],
  PlayerPosition::GOALKEEPER =>  [
    'goals' => ['0' => 0,  '1' => 12, '2' => 24, '3' => 48, '4' => 60],
    'winning_goal' => 3,
    'att_freekick_goal' => 2,
    'ontarget_scoring_att' => 2,
    'goal_assist' => 8,
    'won_contest' => 1.2,
    'big_chance_missed' => -1,
    'att_pen_miss+att_pen_post+att_pen_target' => -3,
    'total_offside' => 0,
    'total_att_assist' => 2.5,
    'big_chance_created' => 3,
    'final_third_entries' => 0,
    'accurate_cross' => 0,
    'accurate_long_balls' => 0.3,
    'accurate_pass/total_pass' => ['0' => 0, '0.845' => 0.5, '0.895' => 1, '0.945' => 1.5],
    'total_pass-accurate_pass' => 0,
    'won_tackle' => 0,
    'outfielder_block' => 0,
    'effective_clearance' => 0,
    'offside_provoked' => 0,
    'fouls' => -1,
    'penalty_conceded' => -5,
    'clean_sheet' => 6,
    'goals_conceded' => -3.5,
    'error_lead_to_shot' => -2.8,
    'error_lead_to_goal' => -6,
    'ball_recovery' => 0.6,
    'interception' => 0,
    'penalty_won' => 5,
    'duel_won' => 0.8,
    'duel_lost' => -0.4,
    'duel_won-aerial_won' => 0,
    'duel_lost-aerial_lost' => 0,
    'aerial_won' => 0,
    'aerial_lost' => 0,
    'saves' => 3,
    'saved_ibox' => 0.2,
    'penalty_save' => 7,
    'good_high_claim' => 2,
    'dive_catch' => 2,
    'punches' => 1,
    'accurate_keeper_sweeper' => 2,
    'mins_played' => ['0' => 0, '1' => 0.2, '15' => 0.5, '46' => 0.8, '90' => 1],
    'own_goals' => -6,
    'yellow_card' => -3,
    'red_card' => -6,
  ]
];

return [
  'FANTASYPOINT_REFERENCE_TABLE_V0' => [
    'Policy' => [
      'goals' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => false,
      ],
      'accurate_pass/total_pass' => [
        'type' => FantasyPolicyType::QUANTILE_MIN_VALUE,
        'weight' => false,
        'minValueCombName' => 'total_pass',
        'minValueRef' => [
          'position' => [
            PlayerDailyPosition::STRIKER => 20,
            PlayerDailyPosition::ATTACKING_MIDFIELDER => 30,
            PlayerDailyPosition::MIDFIELDER => 30,
            PlayerDailyPosition::DEFENSIVE_MIDFIELDER => 50,
            PlayerDailyPosition::DEFENDER => 50,
            PlayerDailyPosition::WING_BACK => 30,
            PlayerDailyPosition::GOALKEEPER => 20,
          ],
          'sub_position' => [
            PlayerPosition::ATTACKER => 20,
            PlayerPosition::MIDFIELDER => 30,
            PlayerPosition::DEFENDER => 50,
            PlayerPosition::GOALKEEPER => 20,
          ]
        ]
      ],
      'mins_played' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => false,
      ],
      'example' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => false,
        // 'order' => OrderType::DESC,
        // 'compare' => CompareType::GTE,
      ],
    ],
    'CombTable' => [
      'position' => [
        PlayerDailyPosition::STRIKER => $commonPositionTable[PlayerPosition::ATTACKER],
        PlayerDailyPosition::ATTACKING_MIDFIELDER => $commonPositionTable[PlayerPosition::MIDFIELDER],
        PlayerDailyPosition::MIDFIELDER => $commonPositionTable[PlayerPosition::MIDFIELDER],
        PlayerDailyPosition::DEFENSIVE_MIDFIELDER => $commonPositionTable[PlayerPosition::MIDFIELDER],
        PlayerDailyPosition::WING_BACK => $commonPositionTable[PlayerPosition::DEFENDER],
        PlayerDailyPosition::DEFENDER => $commonPositionTable[PlayerPosition::DEFENDER],
        PlayerDailyPosition::GOALKEEPER => $commonPositionTable[PlayerPosition::GOALKEEPER],
      ],
      'sub_position' => [
        PlayerPosition::ATTACKER => $commonPositionTable[PlayerPosition::ATTACKER],
        PlayerPosition::MIDFIELDER => $commonPositionTable[PlayerPosition::MIDFIELDER],
        PlayerPosition::DEFENDER => $commonPositionTable[PlayerPosition::DEFENDER],
        PlayerPosition::GOALKEEPER => $commonPositionTable[PlayerPosition::GOALKEEPER],
      ]
    ],
    'Categories' => [
      FantasyPointCategoryType::OFFENSIVE => [
        'goals',                // Goals
        'winning_goal',         // Winning Goal
        'att_freekick_goal',    // Freekick Goals
        'ontarget_scoring_att', // Shots on target
        'goal_assist',          // Successful Dribboles
        'won_contest',          // Assists
        'big_chance_missed',    // Big Chances Missed
        'penalty_missed' => 'att_pen_miss+att_pen_post+att_pen_target', // Penalties Missed
        'total_offside',        // Offsides
      ],
      FantasyPointCategoryType::PASSING => [
        'total_att_assist',     // Key Passes
        'big_chance_created',   // Big Chances Created
        'final_third_entries',  // Passes into Final Third
        'accurate_cross',       // Accurate Crosses
        'accurate_long_balls',  // Accurate Long Passes
        'pass_accuracy' => 'accurate_pass/total_pass',  // Passing Accuracy
        'missed_pass' => 'total_pass-accurate_pass',    // Missed Pass
      ],
      FantasyPointCategoryType::DEFENSIVE => [
        'won_tackle',           // Tackles Won
        'outfielder_block',     // Blocks
        'effective_clearance',  // Clearances
        'offside_provoked',     // Offside Provoked
        'fouls',                // Fouls
        'penalty_conceded',     // Penalties Conceded
        'clean_sheet',          // Clean Sheet
        'goals_conceded',       // Goals Conceded
        'error_lead_to_shot',   // Errors Lead to Shot
        'error_lead_to_goal',   // Errors Lead to Goal
      ],
      FantasyPointCategoryType::DUEL => [
        'ball_recovery',        // Recoveries
        'interception',         // Interceptions
        'penalty_won',          // Penalties Won
        'duel_won',             // Duels Won
        'duel_lost',            // Duels Lost
        'ground_duel_won' => 'duel_won-aerial_won',     // Gound Duels Won
        'ground_duel_lost' => 'duel_lost-aerial_lost',  // Ground Duel Lost
        'aerial_won',           // Aerial Duels Won
        'aerial_lost',          // Aerial Duels Lost
      ],
      FantasyPointCategoryType::GOALKEEPING => [
        'saves',                // Saves
        'saved_ibox',           // Saved in Box
        'penalty_save',         // Penalty Saved
        'good_high_claim',      // High Claims
        'dive_catch',           // Diving Catches
        'punches',              // Punches
        'accurate_keeper_sweeper',  // Acted as Sweeper
      ],
      FantasyPointCategoryType::GENERAL => [
        'mins_played',          // Minutes Player
        'own_goals',            // Own Goals
        'yellow_card',          // Yellow Cards
        'red_card',             // Red Cards
      ],
    ],
    'OrderRef' => [
      'goals' => 1,                 // Goals
      'winning_goal' => 2,          // Winning Goal
      'att_freekick_goal' => 3,     // Freekick Goals
      'ontarget_scoring_att' => 4,  // Shots on target
      'goal_assist' => 5,           // Successful Dribboles
      'won_contest' => 6,           // Assists
      'big_chance_missed' => 7,     // Big Chances Missed
      'penalty_missed' => 8,        // Penalties Missed
      'total_offside' => 9,         // Offsides
      'total_att_assist' => 10,     // Key Passes
      'big_chance_created' => 11,   // Big Chances Created
      'final_third_entries' => 12,  // Passes into Final Third
      'accurate_cross' => 13,       // Accurate Crosses
      'accurate_long_balls' => 14,  // Accurate Long Passes
      'pass_accuracy' => 15,        // Passing Accuracy
      'missed_pass' => 16,          // Missed Pass
      'won_tackle' => 17,           // Tackles Won
      'outfielder_block' => 18,     // Blocks
      'effective_clearance' => 19,  // Clearances
      'offside_provoked' => 20,     // Offside Provoked
      'fouls' => 21,                // Fouls
      'penalty_conceded' => 22,     // Penalties Conceded
      'clean_sheet' => 23,          // Clean Sheet
      'goals_conceded' => 24,       // Goals Conceded
      'error_lead_to_shot' => 25,   // Errors Lead to Shot
      'error_lead_to_goal' => 26,   // Errors Lead to Goal
      'ball_recovery' => 27,        // Recoveries
      'interception' => 28,         // Interceptions
      'penalty_won' => 29,          // Penalties Won
      'duel_won' => 30,             // Duels Won
      'duel_lost' => 31,            // Duels Lost
      'ground_duel_won' => 32,      // Gound Duels Won
      'ground_duel_lost' => 33,     // Ground Duel Lost
      'aerial_won' => 34,           // Aerial Duels Won
      'aerial_lost' => 35,          // Aerial Duels Lost
      'saves' => 36,                // Saves
      'saved_ibox' => 37,           // Saved in Box
      'penalty_save' => 38,         // Penalty Saved
      'good_high_claim' => 39,      // High Claims
      'dive_catch' => 40,           // Diving Catches
      'punches' => 41,              // Punches
      'accurate_keeper_sweeper' => 42,  // Acted as Sweeper
      'mins_played' => 43,          // Minutes Player
      'own_goals' => 44,            // Own Goals
      'yellow_card' => 45,          // Yellow Cards
      'red_card' => 46,             // Red Cards
    ],
  ]
];

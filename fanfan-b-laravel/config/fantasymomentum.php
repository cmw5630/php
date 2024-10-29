<?php

use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;

$commonTable = [
  'total_scoring_att' => 0.00200,
  'touches_opta' => 0.00030,
  'touches_in_opp_box' => 0.00110,
  'total_pass' => 0.00080,
  'total_final_third_passes' => 0.00150,
  'pen_area_entries' => 0.00210,
  'long_pass_own_to_opp' => 0.00025,
  'accurate_goal_kicks' => 0.00015,
  'accurate_layoffs' => 0.00085,
  'accurate_through_ball' => 0.00110,
  'attempts_ibox' => 0.00420,
  'attempts_obox' => 0.00170,
  'att_one_on_one' => 0.00650,
  'ball_recovery' => 0.00095,
  'big_chance_created' => 0.00380,
  'duel_won' => 0.00125,
  'fouled_final_third' => 0.0017,
  'interception' => 0.00065,
  'penalty_won' => 0.00470,
  'poss_won_att3rd' => 0.00130,
  'poss_won_mid3rd' => 0.00090,
  'poss_won_def3rd' => 0.00080,
  'red_card' => 0.00250,
  'total_layoffs' => 0.00025,
  'total_offside' => 0.00085,
  'won_contest' => 0.00070,
  'won_corners' => 0.00160,
];

$specifiedTables = [
  'position' => [
    PlayerDailyPosition::STRIKER => [],
    PlayerDailyPosition::ATTACKING_MIDFIELDER => [],
    PlayerDailyPosition::MIDFIELDER => [],
    PlayerDailyPosition::DEFENSIVE_MIDFIELDER => [],
    PlayerDailyPosition::DEFENDER => [],
    PlayerDailyPosition::WING_BACK => [],
    PlayerPosition::GOALKEEPER => [],
  ],
  'sub_position' => [
    PlayerPosition::ATTACKER => [],
    PlayerPosition::MIDFIELDER => [],
    PlayerPosition::DEFENDER => [],
    PlayerPosition::GOALKEEPER => [],
  ]
];

return [
  'FANTASYMOMENTUM_REFERENCE_TABLE_V0' => [
    'Policy' => [],
    'CombTable' => $commonTable
    // [
    //   'position' => [
    //     PlayerDailyPosition::STRIKER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::STRIKER]),
    //     PlayerDailyPosition::ATTACKING_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::ATTACKING_MIDFIELDER]),
    //     PlayerDailyPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::MIDFIELDER]),
    //     PlayerDailyPosition::DEFENSIVE_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENSIVE_MIDFIELDER]),
    //     PlayerDailyPosition::WING_BACK => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::WING_BACK]),
    //     PlayerDailyPosition::DEFENDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENDER]),
    //     PlayerDailyPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::GOALKEEPER]),
    //   ],
    //   'sub_position' => [
    //     PlayerPosition::ATTACKER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::ATTACKER]),
    //     PlayerPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::MIDFIELDER]),
    //     PlayerPosition::DEFENDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::DEFENDER]),
    //     PlayerPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::GOALKEEPER]),
    //   ]
    // ],
  ]
];

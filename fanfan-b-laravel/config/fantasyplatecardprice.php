<?php

use App\Enums\FantasyMeta\RefPlayerTierType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Models\game\Player;

return [
  'FANTASYPLATECARDPRICE_REFERENCE_TABLE_V0' => [
    'LEAGUE_TIER_CONTRACT_MATRIX' => [
      RefPlayerTierType::S => [
        RefPlayerTierType::S => 0,
        RefPlayerTierType::A => 8.26,
        RefPlayerTierType::B_1 => 11.04, RefPlayerTierType::B_2 => 11.04,
        RefPlayerTierType::C_1 => 13.03, RefPlayerTierType::C_2 => 13.03,
        RefPlayerTierType::D_1 => 14.48, RefPlayerTierType::D_2 => 14.48,
        RefPlayerTierType::E => 15.61
      ],
      RefPlayerTierType::A => [
        RefPlayerTierType::S => -8.26,
        RefPlayerTierType::A => 0,
        RefPlayerTierType::B_1 => 3.06, RefPlayerTierType::B_2 => 3.06,
        RefPlayerTierType::C_1 => 5.25, RefPlayerTierType::C_2 => 5.25,
        RefPlayerTierType::D_1 => 6.84, RefPlayerTierType::D_2 => 6.84,
        RefPlayerTierType::E => 8.09
      ],
      RefPlayerTierType::B_1 => [
        RefPlayerTierType::S => -11.04,
        RefPlayerTierType::A => -3.06,
        RefPlayerTierType::B_1 => 0, RefPlayerTierType::B_2 => 0,
        RefPlayerTierType::C_1 => 2.19, RefPlayerTierType::C_2 => 2.19,
        RefPlayerTierType::D_1 => 3.78, RefPlayerTierType::D_2 => 3.78,
        RefPlayerTierType::E => 5.03,
      ],
      RefPlayerTierType::B_2 => [
        RefPlayerTierType::S => -11.04,
        RefPlayerTierType::A => -3.06,
        RefPlayerTierType::B_1 => 0, RefPlayerTierType::B_2 => 0,
        RefPlayerTierType::C_1 => 2.19, RefPlayerTierType::C_2 => 2.19,
        RefPlayerTierType::D_1 => 3.78, RefPlayerTierType::D_2 => 3.78,
        RefPlayerTierType::E => 5.03,
      ],
      RefPlayerTierType::C_1 => [
        RefPlayerTierType::S => -13.03,
        RefPlayerTierType::A => -5.25,
        RefPlayerTierType::B_1 => -2.19, RefPlayerTierType::B_2 => -2.19,
        RefPlayerTierType::C_1 => 0, RefPlayerTierType::C_2 => 0,
        RefPlayerTierType::D_1 => 1.6, RefPlayerTierType::D_2 => 1.6,
        RefPlayerTierType::E => 2.84
      ],
      RefPlayerTierType::C_2 => [
        RefPlayerTierType::S => -13.03,
        RefPlayerTierType::A => -5.25,
        RefPlayerTierType::B_1 => -2.19, RefPlayerTierType::B_2 => -2.19,
        RefPlayerTierType::C_1 => 0, RefPlayerTierType::C_2 => 0,
        RefPlayerTierType::D_1 => 1.6, RefPlayerTierType::D_2 => 1.6,
        RefPlayerTierType::E => 2.84
      ],
      RefPlayerTierType::D_1 => [
        RefPlayerTierType::S => -14.48,
        RefPlayerTierType::A => -6.84,
        RefPlayerTierType::B_1 => -3.78, RefPlayerTierType::B_2 => -3.78,
        RefPlayerTierType::C_1 => -1.60, RefPlayerTierType::C_2 => -1.60,
        RefPlayerTierType::D_1 => 0, RefPlayerTierType::D_2 => 0,
        RefPlayerTierType::E => 1.24
      ],
      RefPlayerTierType::D_2 => [
        RefPlayerTierType::S => -14.48,
        RefPlayerTierType::A => -6.84,
        RefPlayerTierType::B_1 => -3.78, RefPlayerTierType::B_2 => -3.78,
        RefPlayerTierType::C_1 => -1.60, RefPlayerTierType::C_2 => -1.60,
        RefPlayerTierType::D_1 => 0, RefPlayerTierType::D_2 => 0,
        RefPlayerTierType::E => 1.24
      ],
      RefPlayerTierType::E => [
        RefPlayerTierType::S => -15.61,
        RefPlayerTierType::A => -8.09,
        RefPlayerTierType::B_1 => -5.03, RefPlayerTierType::B_2 => -5.03,
        RefPlayerTierType::C_1 => -2.84, RefPlayerTierType::C_2 => -2.84,
        RefPlayerTierType::D_1 => -1.24, RefPlayerTierType::D_2 => -1.24,
        RefPlayerTierType::E => 0
      ],
    ],
    'LEAGUE_TIER_NON_CONTRACT_MATRIX' => [
      RefPlayerTierType::S => [PlayerPosition::ATTACKER => 8.58, PlayerPosition::MIDFIELDER => 10.48, PlayerPosition::DEFENDER => 12.39, PlayerPosition::GOALKEEPER => 10.48],
      RefPlayerTierType::A => [PlayerPosition::ATTACKER => 6.08, PlayerPosition::MIDFIELDER => 9.36, PlayerPosition::DEFENDER => 10.30, PlayerPosition::GOALKEEPER => 9.36],
      RefPlayerTierType::B_1 => [PlayerPosition::ATTACKER => 5.40, PlayerPosition::MIDFIELDER => 8.49, PlayerPosition::DEFENDER => 9.26, PlayerPosition::GOALKEEPER => 8.49],
      RefPlayerTierType::B_2 => [PlayerPosition::ATTACKER => 5.15, PlayerPosition::MIDFIELDER => 8.23, PlayerPosition::DEFENDER => 8.92, PlayerPosition::GOALKEEPER => 8.23],
      RefPlayerTierType::C_1 => [PlayerPosition::ATTACKER => 5.05, PlayerPosition::MIDFIELDER => 7.72, PlayerPosition::DEFENDER => 8.32, PlayerPosition::GOALKEEPER => 7.72],
      RefPlayerTierType::C_2 => [PlayerPosition::ATTACKER => 4.54, PlayerPosition::MIDFIELDER => 7.06, PlayerPosition::DEFENDER => 7.56, PlayerPosition::GOALKEEPER => 7.06],
      RefPlayerTierType::D_1 => [PlayerPosition::ATTACKER => 3.57, PlayerPosition::MIDFIELDER => 5.64, PlayerPosition::DEFENDER => 6.02, PlayerPosition::GOALKEEPER => 5.64],
      RefPlayerTierType::D_2 => [PlayerPosition::ATTACKER => 3.18, PlayerPosition::MIDFIELDER => 5.09, PlayerPosition::DEFENDER => 5.41, PlayerPosition::GOALKEEPER => 5.09],
      RefPlayerTierType::E => [PlayerPosition::ATTACKER => 2.75, PlayerPosition::MIDFIELDER => 4.45, PlayerPosition::DEFENDER => 4.72, PlayerPosition::GOALKEEPER => 4.45],
    ],
    'PLAYER_CAREER_POSITION_WEIGHT' => [
      PlayerPosition::ATTACKER => [
        'goals' => 0.6,
        'penalty_goals' => 0.1,
        'assists' => 0.3,
        'yellow_cards' => -0.2,
        'red_cards' => -1.5,
      ],
      PlayerPosition::MIDFIELDER => [
        'goals' => 0.6,
        'penalty_goals' => 0.1,
        'assists' => 0.3,
        'yellow_cards' => -0.2,
        'red_cards' => -1.5,
      ],
      PlayerPosition::DEFENDER => [
        'goals' => 0.8,
        'penalty_goals' => 0.5,
        'assists' => 0.5,
        'yellow_cards' => -0.2,
        'red_cards' => -1.5,
      ],
      PlayerPosition::GOALKEEPER => [
        'goals' => 1.5,
        'penalty_goals' => 1,
        'assists' => 1.3,
        'yellow_cards' => -0.2,
        'red_cards' => -1.5,
      ],
    ]
  ]
];

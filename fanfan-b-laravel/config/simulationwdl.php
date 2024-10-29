<?php

use App\Enums\Opta\Player\PlayerSubPosition;

return [
  'additional_team' => [
    'all' => [ // 후보선수 포함
      16 => [
        'add' => 5,
        'level' => 3
      ],
    ],
    // 선발만
    'game_started' => [
      11 => [
        'add' => 4,
        'level' => 2
      ],
      8 => [
        'add' => 3,
        'level' => 1
      ]
    ]
  ],
  'attack_power' => [
    PlayerSubPosition::ST => ['finishing', 'positioning', 'shot'],
    PlayerSubPosition::LW => ['dribbles', 'finishing', 'positioning'],
    PlayerSubPosition::RW => ['dribbles', 'finishing', 'positioning'],
    PlayerSubPosition::LM => ['dribbles', 'chance_create', 'crosses'],
    PlayerSubPosition::RM => ['dribbles', 'chance_create', 'crosses'],
    PlayerSubPosition::AM => ['chance_create', 'positioning', 'finishing'],
    PlayerSubPosition::CM => ['passing', 'chance_create', 'shot'],
    PlayerSubPosition::DM => ['passing', 'long_pass'],
    PlayerSubPosition::CB => ['aerial_duels', 'passing'],
    PlayerSubPosition::LB => ['crosses', 'chance_create'],
    PlayerSubPosition::LWB => ['crosses', 'chance_create'],
    PlayerSubPosition::RB => ['crosses', 'chance_create'],
    PlayerSubPosition::RWB => ['crosses', 'chance_create'],
    PlayerSubPosition::GK => ['passing', 'long_pass'],
  ],
  'defence_power' => [
    PlayerSubPosition::ST => ['aerial_duels', 'power'],
    PlayerSubPosition::LW => ['speed', 'balance'],
    PlayerSubPosition::RW => ['speed', 'balance'],
    PlayerSubPosition::LM => ['speed', 'recoveries'],
    PlayerSubPosition::RM => ['speed', 'recoveries'],
    PlayerSubPosition::AM => ['interceptions', 'recoveries'],
    PlayerSubPosition::CM => ['tackles', 'ground_duels', 'instinct'],
    PlayerSubPosition::DM => ['interceptions', 'ground_duels', 'instinct'],
    PlayerSubPosition::CB => ['clearances', 'blocks', 'blocks', 'aerial_duels'],
    PlayerSubPosition::LB => ['tackles', 'interceptions', 'clearances'],
    PlayerSubPosition::LWB => ['tackles', 'interceptions', 'clearances'],
    PlayerSubPosition::RB => ['tackles', 'interceptions', 'clearances'],
    PlayerSubPosition::RWB => ['tackles', 'interceptions', 'clearances'],
    PlayerSubPosition::GK => ['saves', 'high_claims', 'punches']
  ],
];

<?php

use App\Enums\FantasyCalculator\FantasyPolicyType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;

$commonTable = [
  // General
  'mins_played' => 0.0015,                  // 출전시간
  // 'touches' => null,                        // 볼 터치
  'unsuccessful_touch' => -0.0118,          // 터치미스
  'own_goals' => -0.9257,                   // 자책골
  'yellow_card' => [0 => 0, -0.1115],  // 경고
  'red_card' => [0 => [0 => 0], 1 => [0 => -1.0752, 1 => -0.9375]],   // 퇴장
  // Offensive
  'goals' => [0 => 0, 1 => 0.9913, 2 => 0.9595, 3 => 0.9231, 4 => 0.8789, 5 => 0.8575], // 득점
  'att_freekick_goal' => 0.0131,            // 프리킥 득점
  'goal_assist' => 0.7921,                  // 어시스트
  // 'total_scoring_att' => 0.0257,           // 슈팅
  'ontarget_scoring_att' => 0.0705,         // 유효슈팅
  'hit_woodwork' => 0.0651,                // 골대 맞은 슈팅
  'shot_off_target' => 0.0151,              // 골문 벗어난 슈팅
  'blocked_scoring_att' => 0.0477,         // 차단된 슈팅
  'penalty_won' => 0.4991,                  // PK 획득
  'won_contest' => 0.0547,                  // 드리블 성공
  'total_offside' => -0.0108,               // 오프사이드
  'dispossessed' => -0.0351,                // 볼뺏김
  'big_chance_missed' => -0.0691,           // 빅찬스 미스
  'att_pen_miss+att_pen_post+att_pen_target' => -0.4289,                // PK 실축

  // 패싱
  // 'accurate_pass' => null,                  // 패스 성공
  // 'total_pass-accurate_pass' => null,       // 패스 미스
  // 'accurate_long_balls' => null,            // 롱패스 성공
  // 'final_third_entries' => null,            // 파이털써드 침투 패스 성공
  'accurate_cross' => 0.0485,               // 크로스 성공
  'accurate_corners_intobox' => 0.0411,     // 박스 내 투입 성공 코너킥
  'accurate_layoffs' => 0.0255,             // 레이오프 성공
  'accurate_through_ball' => 0.0415,        // 스루 패스 성공 
  'total_att_assist' => 0.0869,          // 키패스
  'big_chance_created' => 0.1005,           // 빅찬스 생성 패스
  'accurate_pull_back' => 0.0102,           // 풀백패스 성공
  // 수비
  // 'goals_conceded' => null,                 // 실점
  'penalty_conceded' => -0.4985,            // PK 허용
  'challenge_lost' => -0.0878,              // 드리블 돌파 허용
  'effective_clearance' => 0.0372,          // 클리어링 
  'effective_head_clearance' => 0.0193,     // 헤더 클리어링 
  'won_tackle' => 0.0666,                   // 태클 성공
  'clearance_off_line' => 0.4186,           // 골라인 직전 걷어내기
  'interception' => 0.0605,                 // 인터셉트
  'last_man_tackle' => 0.3312,              // 최종 수비수 태클 성공
  'outfielder_block' => 0.0502,             // 슈팅 차단 
  'offside_provoked' => 0.0216,             // 오프사이드 유도 
  'blocked_cross' => 0.0229,             // 오프사이드 유도 

  // 경합 ->
  // 'duel_won-aerial_won' => null,            // 지상 경합
  // 'aerial_won' => null,                     // 공중볼 경합
  // 'ball_recovery' => null,                  // 볼 리커버리 성공
  'was_fouled' => 0.0051,                   // 피파울
  'fouled_final_third' => 0.0027,           // 파이널써드 지역 피파울
  'fouls' => -0.0077,                       // 파울
  'duel_lost-aerial_lost' => -0.0324,       // 지상 경합 실패
  'aerial_lost' => -0.0314,                 // 공중볼 경합 실패
  'error_lead_to_shot' => -0.1001,          // 슈팅으로 연결된 실책
  'error_lead_to_goal' => -1.2021,          // 실점으로 연결된 실책

  // 골키핑 -> 
  'saved_ibox' => 0.0382,                   // 박스 내 선방
  'penalty_save' => 0.7988,                 // PK 선방
  'dive_catch' => 0.0282,                   // 다이빙 캐치
  'dive_save' => 0.0681,                    // 다이빙 선방
  'accurate_keeper_sweeper' => 0.0427,      // 스위핑 성공
  'punches' => 0.0058,                      // 펀칭
  'accurate_keeper_throws' => 0.0249,       // 던지기 성공
  'good_high_claim' => 0.0723,              // 공중볼 처리 성공
  'cross_not_claimed' => -0.1025,           // 공중볼 처리 실패
  'accurate_goal_kicks' => 0.0090,          // 골킥 성공
  'gk_smother' => 0.0318,                   // 스무더 성공
  'saves' => 0.0971,
  'saves_bonus' => [0 => [0 => 0], 5 => [0 => [0 => 0], '0.7' => [0 => 0.3995, 1 => 0.4997]]],
  // 선방 5개 이상
  // 선방 5개 이상 & 선방율 70% 이상
  // 선방 5개 이상 & 선방율 70% 이상 & 클린시트 성공
];

$specifiedTables = [
  'position' => [
    PlayerDailyPosition::STRIKER => [
      'touches' => 0.0027,
      'accurate_pass' => 0.0043,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0231,      // 패스 미스
      'accurate_long_balls' => 0.0175,            // 롱패스 성공
      'final_third_entries' => 0.0261,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.0451,
      'goals_conceded' => [0 => 0, 1 => -0.0225], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0399, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0405, '0.8' => 0.0705]],  //공중볼 경합
      'ball_recovery' => 0.0181,                  // 볼 리커버리 성공
    ],
    PlayerDailyPosition::ATTACKING_MIDFIELDER => [
      'touches' => 0.0029,
      'accurate_pass' => 0.0061,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0247,      // 패스 미스
      'accurate_long_balls' => 0.0155,            // 롱패스 성공
      'final_third_entries' => 0.0221,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.0587,
      'goals_conceded' => [0 => 0, 1 => -0.0295], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0399, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0385, '0.8' => 0.0695]],  //공중볼 경합
      'ball_recovery' => 0.0207,                  // 볼 리커버리 성공
    ],
    PlayerDailyPosition::MIDFIELDER => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0057,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0265,      // 패스 미스
      'accurate_long_balls' => 0.0151,            // 롱패스 성공
      'final_third_entries' => 0.0151,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.0821,
      'goals_conceded' => [0 => 0, 1 => -0.0535, 2 => -0.0675, 3 => -0.0721, 4 => -0.0875], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0452, '0.8' => 0.0735]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0385, '0.8' => 0.0695]],  //공중볼 경합
      'ball_recovery' => 0.0237,                  // 볼 리커버리 성공
    ],
    PlayerDailyPosition::DEFENSIVE_MIDFIELDER => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0055,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0253,      // 패스 미스
      'accurate_long_balls' => 0.0145,            // 롱패스 성공
      'final_third_entries' => 0.0101,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.1188,
      'goals_conceded' => [0 => 0, 1 => -0.0585, 2 => -0.0735, 3 => -0.0915, 4 => -0.1005], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0442, '0.8' => 0.0735]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0401, '0.8' => 0.0711]],  //공중볼 경합
      'ball_recovery' => 0.0237,                  // 볼 리커버리 성공
    ],
    PlayerDailyPosition::DEFENDER => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0058,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0261,      // 패스 미스
      'accurate_long_balls' => 0.0091,            // 롱패스 성공
      'final_third_entries' => 0.0061,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.2895,
      'goals_conceded' => [0 => 0, 1 => -0.0695, 2 => -0.0815, 3 => -0.1355, 4 => -0.1995], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0431, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0361], 4 => [0 => 0.0412, '0.8' => 0.0701]],  //공중볼 경합
      'ball_recovery' => 0.0191,                  // 볼 리커버리 성공
    ],
    PlayerDailyPosition::WING_BACK => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0054,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0232,      // 패스 미스
      'accurate_long_balls' => 0.0112,            // 롱패스 성공
      'final_third_entries' => 0.0071,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.2612,
      'goals_conceded' => [0 => 0, 1 => -0.0695, 2 => -0.0815, 3 => -0.1355, 4 => -0.1995], // 실점
      'duel_won-aerial_won' => [0 => [0 => 0.0362], 3 => [0 => 0.0431, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0361], 4 => [0 => 0.0412, '0.8' => 0.0701]],  //공중볼 경합
      'ball_recovery' => 0.0191,                  // 볼 리커버리 성공
    ],
    PlayerPosition::GOALKEEPER => [
      'touches' => 0.0037,
      'accurate_pass' => 0.0007,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0011,      // 패스 미스
      'accurate_long_balls' => 0.0022,            // 롱패스 성공
      'final_third_entries' => 0.0025,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.4282,
      'goals_conceded' => [0 => 0, 1 => -0.1595, 2 => -0.1995, 3 => -0.3215, 4 => -0.4015], // 실점
      'duel_won-aerial_won' =>  [0 => [0 => 0.0412]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0412]],  //공중볼 경합
      'ball_recovery' => 0.0109,                  // 볼 리커버리 성공
    ],
  ],
  'sub_position' => [
    PlayerPosition::ATTACKER => [
      'touches' => 0.0027,
      'accurate_pass' => 0.0043,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0231,      // 패스 미스
      'accurate_long_balls' => 0.0175,            // 롱패스 성공
      'final_third_entries' => 0.0261,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.0451,
      'goals_conceded' => [0 => 0, 1 => -0.0205], // 실점
      'duel_won-aerial_won' =>  [0 => [0 => 0.0362], 3 => [0 => 0.0399, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0405, '0.8' => 0.0705]],  //공중볼 경합
      'ball_recovery' => 0.0181,                  // 볼 리커버리 성공
    ],
    PlayerPosition::MIDFIELDER => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0055,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0247,      // 패스 미스
      'accurate_long_balls' => 0.0151,            // 롱패스 성공
      'final_third_entries' => 0.0151,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.0587,
      'goals_conceded' => [0 => 0, 1 => -0.0435, 2 => -0.0675, 3 => -0.0911, 4 => -0.1475], // 실점
      'duel_won-aerial_won' =>  [0 => [0 => 0.0362], 3 => [0 => 0.0412, '0.8' => 0.0735]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0361], 3 => [0 => 0.0385, '0.8' => 0.0695]],  //공중볼 경합
      'ball_recovery' => 0.0257,                  // 볼 리커버리 성공
    ],
    PlayerPosition::DEFENDER => [
      'touches' => 0.0032,
      'accurate_pass' => 0.0056,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0265,      // 패스 미스
      'accurate_long_balls' => 0.0091,            // 롱패스 성공
      'final_third_entries' => 0.0052,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.2791,
      'goals_conceded' => [0 => 0, 1 => -0.0695, 2 => -0.0815, 3 => -0.1355, 4 => -0.1995], // 실점
      'duel_won-aerial_won' =>  [0 => [0 => 0.0362], 3 => [0 => 0.0431, '0.8' => 0.0715]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0362], 4 => [0 => 0.0401, '0.8' => 0.0701]],  //공중볼 경합
      'ball_recovery' => 0.0191,                  // 볼 리커버리 성공
    ],
    PlayerPosition::GOALKEEPER => [
      'touches' => 0.0037,
      'accurate_pass' => 0.0006,                  // 패스 성공
      'total_pass-accurate_pass' => -0.0011,      // 패스 미스
      'accurate_long_balls' => 0.0021,            // 롱패스 성공
      'final_third_entries' => 0.0025,            // 파이털써드 침투 패스 성공
      'clean_sheet' => 0.4282,
      'goals_conceded' => [0 => 0, 1 => -0.1195, 2 => -0.1595, 3 => -0.2715, 4 => -0.3415], // 실점
      'duel_won-aerial_won' =>  [0 => [0 => 0.0412]],  // 지상 경합
      'aerial_won' => [0 => [0 => 0.0412]],  //공중볼 경합
      'ball_recovery' => 0.0109,                  // 볼 리커버리 성공
    ],
  ]
];

return [
  'FANTASYRATING_REFERENCE_TABLE_V0' => [
    'Policy' => [
      'yellow_card' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => true,
      ],
      'red_card' => [
        'type' => FantasyPolicyType::QUANTILE_QUANTILE_CONDITIONS,
        'weight' => true,
        'conditionCombNames' => ['second_yellow'],
      ],
      'goals' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => true,
      ],
      'goals_conceded' => [
        'type' => FantasyPolicyType::QUANTILE,
        'weight' => true,
      ],
      'duel_won-aerial_won' => [
        'type' => FantasyPolicyType::QUANTILE_QUANTILE_CONDITIONS,
        'weight' => true,
        'conditionCombNames' => ['(duel_won-aerial_won)/((duel_won-aerial_won)+(duel_lost-aerial_lost))'],
      ],
      'aerial_won' => [
        'type' => FantasyPolicyType::QUANTILE_QUANTILE_CONDITIONS,
        'weight' => true,
        'conditionCombNames' => ['aerial_won/(aerial_won+aerial_lost)'],
      ],
      'saves_bonus' => [
        'type' => FantasyPolicyType::QUANTILE_QUANTILE_CONDITIONS,
        'weight' => false,
        'conditionCombNames' => ['saved_ibox/saves', 'clean_sheet'],
      ],
    ],
    'CombTable' => [
      'position' => [
        PlayerDailyPosition::STRIKER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::STRIKER]),
        PlayerDailyPosition::ATTACKING_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::ATTACKING_MIDFIELDER]),
        PlayerDailyPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::MIDFIELDER]),
        PlayerDailyPosition::DEFENSIVE_MIDFIELDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENSIVE_MIDFIELDER]),
        PlayerDailyPosition::WING_BACK => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::WING_BACK]),
        PlayerDailyPosition::DEFENDER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::DEFENDER]),
        PlayerDailyPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['position'][PlayerDailyPosition::GOALKEEPER]),
      ],
      'sub_position' => [
        PlayerPosition::ATTACKER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::ATTACKER]),
        PlayerPosition::MIDFIELDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::MIDFIELDER]),
        PlayerPosition::DEFENDER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::DEFENDER]),
        PlayerPosition::GOALKEEPER => array_merge($commonTable, $specifiedTables['sub_position'][PlayerPosition::GOALKEEPER]),
      ]
    ],
    'Categories' => [
      FantasyPointCategoryType::GENERAL => [
        'mins_played',
        'touches',
        'unsuccessful_touch',
        'own_goals',
        'yellow_card',
        'red_card',
      ],

      FantasyPointCategoryType::OFFENSIVE => [
        'goals',
        'att_freekick_goal',
        'goal_assist',
        // 'total_scoring_att',
        'ontarget_scoring_att',
        'hit_woodwork',
        'shot_off_target',
        'blocked_scoring_att',
        'penalty_won',
        'won_contest',
        'total_offside',
        'dispossessed',
        'big_chance_missed',
        'penalty_missed' => 'att_pen_miss+att_pen_post+att_pen_target',
      ],

      FantasyPointCategoryType::PASSING => [
        'accurate_pass',
        'accurate_cross',
        'missed_pass' => 'total_pass-accurate_pass',
        'accurate_corners_intobox',
        'accurate_layoffs',
        'accurate_through_ball',
        'accurate_long_balls',
        'total_att_assist',
        'accurate_pull_back',
        'big_chance_created',
        'final_third_entries',
      ],

      FantasyPointCategoryType::DEFENSIVE =>  [
        'penalty_conceded',
        'effective_clearance',
        'effective_head_clearance',
        'won_tackle',
        'clearance_off_line',
        'challenge_lost',
        'interception',
        'last_man_tackle',
        'offside_provoked',
        'outfielder_block',
      ],

      FantasyPointCategoryType::DUEL => [
        'fouls',
        'was_fouled',
        'aerial_won',
        'aerial_lost',
        'ground_duel_won' => 'duel_won-aerial_won',
        'ground_duel_lost' => 'duel_lost-aerial_lost',
        'ball_recovery',
        'error_lead_to_goal',
        'error_lead_to_shot',
        'fouled_final_third',
      ],

      FantasyPointCategoryType::GOALKEEPING => [
        'saves',
        'penalty_save',
        'clean_sheet',
        'goals_conceded',
        'dive_catch',
        'dive_save',
        'saved_ibox',
        'accurate_keeper_sweeper',
        'punches',
        'accurate_keeper_throws',
        'good_high_claim',
        'accurate_goal_kicks',
        'cross_not_claimed',
        'gk_smother',
      ],
    ],
  ]
];

<?php

use App\Enums\StatCategory;

return [
  'rank_grades' => [
    'EPL' => [
      'Champions League' => [1, 2, 3, 4],
      'Europa League' => [5],
      'Relegation' => [18, 19, 20]
    ],
    'KL1' => [
      'AFC Champions League' => [1, 2, 3],
      'Relegation qualificatio' => [10, 11],
      'Relegation' => [12],
    ],
  ],
  // 각 배열의 첫번째가 default
  'categories' => [
    'team' => [
      StatCategory::SUMMARY => [
        'points',          // 승점
        'games_played',    // 경기수
        'matches_won',     // 승
        'matches_lost',    // 패
        'matches_drawn',   // 무
        'goals',           // 득점
        'goals_conceded',  // 실점
        'goaldifference',  // 득실차
        'rank_status',     // 순위 구분
      ],
      StatCategory::ATTACKING => [
        'goals',                // 득점
        'total_shots',           // 전체슈팅
        'shots_on_target',      // 유효슈팅
        'goal_assists',         // 어시스트
        'successful_dribbles',  // 드리블 성공
        'penalty_goals',        // PK 득점
        'offsides'              // 오프사이드
      ],
      StatCategory::PASSING => [
        'passing_accuracy',               // 패스 성공%
        'total_passes',                   // 전체 패스
        'total_successful_passes',        // 패스 성공
        'passing_per_opp_half',           // 상대 지역 패스 성공률
        'successful_crossesopenplay',     // 크로스 성공
        'key_passes',                     // 키패스 성공
        'successful_long_passes',         // 롱패스 성공
      ],
      StatCategory::DEFENSIVE => [
        'tackles_won',                  // 태클
        'interceptions',                // 인터셉트
        'total_clearances',             // 클리어링
        'blocks',                       // 슈팅 차단
        'penalties_conceded',           // PK 허용
        'total_fouls_conceded',         // 파울
        'goals_conceded',               // 실점
      ],
      StatCategory::DUELS => [
        'possession_percentage',        // 점유율
        'duels',                        // 경합 시도
        'duelswon',                     // 경합 성공
        'ground_duels',                 // 지상 경합 시도
        'ground_duelswon',              // 지상 경합 성공
        'aerial_duels',                 // 공중볼 경합 시도
        'aerial_duelswon',              // 공중볼 경합 성공
      ],
      StatCategory::GOALKEEPING => [
        'clean_sheets',                 // 클린시트
        'catches',                      // 공중볼 처리 성공
        'drops',                        // 공중볼 처리 실패
        'g_k_successful_distribution',  // GK 정확한 볼 배급
        'own_goals_conceded',           // 자책골
        'yellow_cards',                 // 경고
        'total_red_cards',              // 퇴장
      ],
    ],
    'player' => [
      StatCategory::SUMMARY => [
        'rating',          // 평점
        'games_played',    // 경기수
        'goals',           // 득점
        'goal_assists',    // 어시스트
        'total_shots',     // 전체 슈팅
        'total_passes',    // 전체 패스
        'clean_sheets',    // 클린시트
      ],
      StatCategory::ATTACKING => [
        'goals',                // 득점
        'total_shots',           // 전체슈팅
        'shots_on_target',      // 유효슈팅
        'goal_assists',         // 어시스트
        'successful_dribbles',  // 드리블 성공
        'penalty_goals',        // PK 득점
        'offsides'              // 오프사이드
      ],
      StatCategory::PASSING => [
        'passing_accuracy',               // 패스 성공%
        'total_passes',                   // 전체 패스
        'total_successful_passes',        // 패스 성공
        'through_balls',                  // 스루 패스 성공
        'successful_crossesopenplay',     // 크로스 성공
        'key_passes',                     // 키패스 성공
        'successful_long_passes',         // 롱패스 성공
      ],
      StatCategory::DEFENSIVE => [
        'total_tackles',                // 태클
        'interceptions',                // 인터셉트
        'total_clearances',             // 클리어링
        'blocks',                       // 슈팅 차단
        'penalties_conceded',           // PK 허용
        'total_fouls_conceded',         // 파울
        'last_man_tackle',               // 실점
      ],
      StatCategory::DUELS => [
        'duelswon',                     // 경합 성공
        'recoveries',                   // 볼 리커버리
        'duels',                        // 경합 시도
        'ground_duels',                 // 지상 경합 시도
        'ground_duelswon',              // 지상 경합 성공
        'aerial_duels',                 // 공중볼 경합 시도
        'aerial_duelswon',              // 공중볼 경합 성공
      ],
      StatCategory::GOALKEEPING => [
        'clean_sheets',                 // 클린시트
        'saves_made',                   // 선방
        'savesfrom_penalty',            // PK 선방
        'catches',                      // 공중볼 처리 성공
        'punches',                      // 펀칭
        'yellow_cards',                 // 경고
        'total_red_cards',              // 퇴장
      ],
    ],
    'player_per' => [
      StatCategory::SUMMARY => [
        'goals',           // 득점
        'games_played',    // 게임 수
        'goal_assists',    // 어시스트
        'total_shots',     // 전체 슈팅
        'total_passes',    // 전체 패스
      ],
      StatCategory::ATTACKING => [
        'goals',                // 득점
        'total_shots',           // 전체슈팅
        'shots_on_target',      // 유효슈팅
        'goal_assists',         // 어시스트
        'successful_dribbles',  // 드리블 성공
      ],
      StatCategory::PASSING => [
        'total_passes',                   // 전체 패스
        'total_successful_passes',        // 패스 성공
        'through_balls',                  // 스루 패스 성공
        'successful_crossesopenplay',     // 크로스 성공
        'key_passes',                     // 키패스 성공
        'successful_long_passes',         // 롱패스 성공
      ],
      StatCategory::DEFENSIVE => [
        'total_tackles',                // 태클
        'interceptions',                // 인터셉트
        'total_clearances',             // 클리어링
        'blocks',                       // 슈팅 차단
        'total_fouls_conceded',         // 파울
        'last_man_tackle',               // 실점
      ],
      StatCategory::DUELS => [
        'duelswon',                     // 경합 성공
        'recoveries',                   // 볼 리커버리
        'duels',                        // 경합 시도
        'ground_duels',                 // 지상 경합 시도
        'ground_duelswon',              // 지상 경합 성공
        'aerial_duels',                 // 공중볼 경합 시도
        'aerial_duelswon',              // 공중볼 경합 성공
      ],
      StatCategory::GOALKEEPING => [
        'saves_made',                   // 선방
        'catches',                      // 공중볼 처리 성공
        'punches',                      // 펀칭
      ],
    ],
  ]
];

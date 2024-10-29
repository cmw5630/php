<?php
// 드래프트 계산과 관계없이 유저에게 보여지는 stat들(opta_player_daily_stat)에 대한 카테고리 정보만을 위한 config 파일
// touches 필드 touches_opta로

use App\Enums\FantasyCalculator\FantasyDraftCategoryType;


return [
  'FANTASYDRAFTEXTRA_REFERENCE_TABLE_V0' => [
    'Categories' => [
      FantasyDraftCategoryType::SUMMARY => [
        // 속성이름, // 표시정보 (툴팁)
        'rating', // Rating
        'position', // POS (Position)
        'game_started', // GS (Game Started)
        'mins_played', // MP (Minutes Played)
        'goals', // G (Goals)
        'goal_assist', // Ast (Assists)
        'yellow_card', // YC (Yellow Cards)
        'red_card', // RC (RedCards)
      ],
      FantasyDraftCategoryType::ATTACKING => [
        'ontarget_scoring_att', // Shot (Total Shots)
        'total_scoring_att',  // ontarget_scoring_att/total_scoring_att
        'won_contest', // Drb (Successful Dribbles)
        'total_contest', // won_contest/total_contest
        'winning_goal', // G_win (Winning Goal)
        'att_freekick_goal', // G_FK (Freekick Goals)
        'big_chance_missed', // BC_Miss (Big Chance Missed)
        'att_pen_miss', // PK (Penalties Missed)
        'att_pen_post', // att_pen_miss + att_pen_post + att_pen_target
        'att_pen_target', // --
        'total_offside', // Off (Offsides)
      ],
      FantasyDraftCategoryType::PASSING => [
        'touches_opta', // Touch (All Touches)
        'accurate_pass', //Pass (Accurate Passes)
        'total_pass', // accurate_pass/total_pass
        'total_att_assist', // KP (key passes)
        'big_chance_created', // BC_Pass (Big Chances Created)
        'final_third_entries', // FT_Pass (Passes into Final Third)
        'accurate_cross', // AC (Accurate Crosses)
        'total_cross', // accurate_cross/total_cross
        'accurate_long_balls', // LP (Accurate Long Passes)
        'total_long_balls',  // accurate_long_balls/total_long_balls
      ],
      FantasyDraftCategoryType::DEFENSIVE => [
        'won_tackle', // Tkl (Tackles Won)
        'outfielder_block', // Blk (Blocks)
        'effective_clearance', // Clr (Clearances)
        'offside_provoked', // Off_P (Offside Provoked)
        'fouls', // FC (Fouls)
        'error_lead_to_shot', // Err_S (Errors Lead to Shot)
        'error_lead_to_goal', // Err_G (Errors Lead to Goal)
      ],
      FantasyDraftCategoryType::DUEL => [
        'duel_won', // GD (Ground Duels) // AD (Aerial Duels)
        'duel_lost',
        'aerial_won', // duelWon-aerialWon / (duelWon-aerialWon)+(duelLost-aerialLost)
        'aerial_lost',
        'duel_lost', // --
        'ball_recovery', // Rcv (Recovery)
        'interception', // Int (Interceptions)
        'penalty_won', // PK_W (Penalties Won)
        'penalty_conceded', // PK_C (Penalties Conceded)
        'own_goals', // OG (Own Goals)
      ],
      FantasyDraftCategoryType::GOALKEEPING => [
        'saves', // SV (Saves)
        'goals_conceded', // GC (Goals Conceded)
        'penalty_save', // PK_SV (Penalty Saved)
        'good_high_claim', // HC (High Claims)
        'dive_catch', // Dive (Dive Catches)
        'punches', // PC (Punches)
        'accurate_keeper_sweeper', // SW (Acted as Sweeper)
      ],
    ]
  ]
];

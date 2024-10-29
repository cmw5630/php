<?php

namespace Database\Seeders;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Models\meta\RefTeamDefaultProjection;
use Illuminate\Database\Seeder;
use Str;

class RefTeamDefaultProjectionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (RefTeamDefaultProjection::all()->isNotEmpty()) {
      RefTeamDefaultProjection::truncate();
    }

    $seedData = [
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'a3nyxabgsqlnqfkeg41m6tnpp',  // 맨시티
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 15.38,
        Str::lower(PlayerPosition::MIDFIELDER) => 14.57,
        Str::lower(PlayerPosition::DEFENDER) => 12.24,
        Str::lower(PlayerPosition::GOALKEEPER) => 5.79,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'c8h9bw1l82s06h77xxrelzhur',  // 리버풀
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 16.61,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.19,
        Str::lower(PlayerPosition::DEFENDER) => 12.7,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.7,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '9q0arba2kbnywth8bkxlhgmdr',  // 첼시
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 10.28,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.51,
        Str::lower(PlayerPosition::DEFENDER) => 12.78,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.26,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '22doj4sgsocqpxw45h607udje',  // 토트넘
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 12.23,
        Str::lower(PlayerPosition::MIDFIELDER) => 14.01,
        Str::lower(PlayerPosition::DEFENDER) => 8.89,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.41,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '4dsgumo7d4zupm2ugsvm4zm4d',  // 아스날
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 11.15,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.31,
        Str::lower(PlayerPosition::DEFENDER) => 10.53,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.51,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '6eqit8ye8aomdsrrq0hk3v7gh',  // 맨유
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 7.96,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.76,
        Str::lower(PlayerPosition::DEFENDER) => 10.42,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.08,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '4txjdaqveermfryvbfrr4taf7',  // 웨스트햄
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 9.46,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.95,
        Str::lower(PlayerPosition::DEFENDER) => 8.81,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.25,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => 'avxknfz4f6ob0rv9dbnxdzde0',  // 레스터시티(2부?)
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.881,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.012,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.028,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 0.902,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'e5p0ehyguld7egzhiedpdnc3w',  // 브라이튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 7.41,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.97,
        Str::lower(PlayerPosition::DEFENDER) => 11.39,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.35,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'b9si1jn1lfxfund69e9ogcu2n',  // 울버햄튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 8.26,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.24,
        Str::lower(PlayerPosition::DEFENDER) => 9.15,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.57,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '7vn2i2kd35zuetw6b38gw9jsz',  // 뉴캐슬
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 13.59,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.85,
        Str::lower(PlayerPosition::DEFENDER) => 9.43,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.01,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1c8m2ko0wxq1asfkuykurdr0y',  //크리스탈팰리스
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 11.14,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.62,
        Str::lower(PlayerPosition::DEFENDER) => 9.65,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.77,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '7yx5dqhhphyvfisohikodajhv',  // 브렌트포드
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 12.39,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.06,
        Str::lower(PlayerPosition::DEFENDER) => 8.98,
        Str::lower(PlayerPosition::GOALKEEPER) => 11.44,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'b496gs285it6bheuikox6z9mj',  // 아스톤빌라
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 9.67,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.59,
        Str::lower(PlayerPosition::DEFENDER) => 10.01,
        Str::lower(PlayerPosition::GOALKEEPER) => 9.53,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => 'd5ydtvt96bv7fq04yqm2w2632',  // 사우스햄튼
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.958,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.056,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.083,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.089,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'ehd2iemqmschhj2ec0vayztzz',  // 에버튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 8.18,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.93,
        Str::lower(PlayerPosition::DEFENDER) => 9.18,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.35,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => '48gk2hpqtsl6p9sx9kjhaydq4',  // 리즈유나이티드 (2부?)
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 1.026,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.058,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.119,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'hzqh7z0mdl3v7gwete66syxp', // 풀럼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 8.1,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.13,
        Str::lower(PlayerPosition::DEFENDER) => 11.56,
        Str::lower(PlayerPosition::GOALKEEPER) => 9.45,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1pse9ta7a45pi2w2grjim70ge',  // 본머스
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 7.55,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.91,
        Str::lower(PlayerPosition::DEFENDER) => 7.61,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.44,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1qtaiy11gswx327s0vkibf70n',  // 노팅엄
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 5.35,
        Str::lower(PlayerPosition::MIDFIELDER) => 9.26,
        Str::lower(PlayerPosition::DEFENDER) => 9.73,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.45,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '64bxxwu2mv2qqlv0monbkj1om',  // 번리
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 8.02,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.64,
        Str::lower(PlayerPosition::DEFENDER) => 10.15,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.589,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'bws31egwjda253q9lvykgnivo',  // 쉐필드
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 6.42,
        Str::lower(PlayerPosition::MIDFIELDER) => 8.51,
        Str::lower(PlayerPosition::DEFENDER) => 8.12,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.07,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'aksa492u5hf93giwcn2zt1nzz',  // 루턴타운
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 5.13,
        Str::lower(PlayerPosition::MIDFIELDER) => 6.81,
        Str::lower(PlayerPosition::DEFENDER) => 6.5,
        Str::lower(PlayerPosition::GOALKEEPER) => 4.86,
      ],
      // HOME
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'a3nyxabgsqlnqfkeg41m6tnpp',  // 맨시티
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 17.85,
        Str::lower(PlayerPosition::MIDFIELDER) => 16.25,
        Str::lower(PlayerPosition::DEFENDER) => 11.28,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.57,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'c8h9bw1l82s06h77xxrelzhur',  // 리버풀
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 19.25,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.55,
        Str::lower(PlayerPosition::DEFENDER) => 15.13,
        Str::lower(PlayerPosition::GOALKEEPER) => 9.88,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '9q0arba2kbnywth8bkxlhgmdr',  // 첼시
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 12.63,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.91,
        Str::lower(PlayerPosition::DEFENDER) => 12.84,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.35,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '22doj4sgsocqpxw45h607udje',  // 토트넘
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 12.63,
        Str::lower(PlayerPosition::MIDFIELDER) => 15.25,
        Str::lower(PlayerPosition::DEFENDER) => 9.73,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.251,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '4dsgumo7d4zupm2ugsvm4zm4d',  // 아스날
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 14.38,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.45,
        Str::lower(PlayerPosition::DEFENDER) => 10.78,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.66,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '6eqit8ye8aomdsrrq0hk3v7gh',  // 맨유
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 10.58,
        Str::lower(PlayerPosition::MIDFIELDER) => 15.14,
        Str::lower(PlayerPosition::DEFENDER) => 12.36,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.78,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '4txjdaqveermfryvbfrr4taf7',  // 웨스트햄
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 11.32,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.08,
        Str::lower(PlayerPosition::DEFENDER) => 9.5,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.15,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => 'avxknfz4f6ob0rv9dbnxdzde0',  // 레스터시티(2부?)
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.881,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.012,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.028,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 0.902,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'e5p0ehyguld7egzhiedpdnc3w',  // 브라이튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 8.22,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.98,
        Str::lower(PlayerPosition::DEFENDER) => 12.13,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.51,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'b9si1jn1lfxfund69e9ogcu2n',  // 울버햄튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 9.87,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.18,
        Str::lower(PlayerPosition::DEFENDER) => 9.79,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.88,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '7vn2i2kd35zuetw6b38gw9jsz',  // 뉴캐슬
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 14.95,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.99,
        Str::lower(PlayerPosition::DEFENDER) => 11.64,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.31,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1c8m2ko0wxq1asfkuykurdr0y',  // 크리스탈팰리스
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 13.27,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.88,
        Str::lower(PlayerPosition::DEFENDER) => 10.78,
        Str::lower(PlayerPosition::GOALKEEPER) => 7.98,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '7yx5dqhhphyvfisohikodajhv',  // 브렌트포드
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 13.37,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.23,
        Str::lower(PlayerPosition::DEFENDER) => 11.28,
        Str::lower(PlayerPosition::GOALKEEPER) => 10.69,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'b496gs285it6bheuikox6z9mj',  // 아스톤빌라
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 10.47,
        Str::lower(PlayerPosition::MIDFIELDER) => 13.25,
        Str::lower(PlayerPosition::DEFENDER) => 10.06,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.7,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => 'd5ydtvt96bv7fq04yqm2w2632',  // 사우스햄튼
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.958,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.056,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.083,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.089,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'ehd2iemqmschhj2ec0vayztzz',  // 에버튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 9.56,
        Str::lower(PlayerPosition::MIDFIELDER) => 14.01,
        Str::lower(PlayerPosition::DEFENDER) => 10.3,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.53,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'team_id' => '48gk2hpqtsl6p9sx9kjhaydq4',  // 리즈유나이티드 (2부?)
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 1.026,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.058,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.119,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'hzqh7z0mdl3v7gwete66syxp', // 풀럼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 10.57,
        Str::lower(PlayerPosition::MIDFIELDER) => 10.45,
        Str::lower(PlayerPosition::DEFENDER) => 10.45,
        Str::lower(PlayerPosition::GOALKEEPER) => 9.53,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1pse9ta7a45pi2w2grjim70ge',  // 본머스
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 8.64,
        Str::lower(PlayerPosition::MIDFIELDER) => 12.24,
        Str::lower(PlayerPosition::DEFENDER) => 8.85,
        Str::lower(PlayerPosition::GOALKEEPER) => 9.1,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '1qtaiy11gswx327s0vkibf70n',  // 노팅엄
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 9.39,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.61,
        Str::lower(PlayerPosition::DEFENDER) => 9.86,
        Str::lower(PlayerPosition::GOALKEEPER) => 8.13,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => '64bxxwu2mv2qqlv0monbkj1om',  // 번리
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 8.67,
        Str::lower(PlayerPosition::MIDFIELDER) => 11.3,
        Str::lower(PlayerPosition::DEFENDER) => 10.04,
        Str::lower(PlayerPosition::GOALKEEPER) => 6.84,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'bws31egwjda253q9lvykgnivo',  // 쉐필드
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 6.94,
        Str::lower(PlayerPosition::MIDFIELDER) => 9.04,
        Str::lower(PlayerPosition::DEFENDER) => 8.03,
        Str::lower(PlayerPosition::GOALKEEPER) => 5.47,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'team_id' => 'aksa492u5hf93giwcn2zt1nzz',  // 루턴타운
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 5.55,
        Str::lower(PlayerPosition::MIDFIELDER) => 7.23,
        Str::lower(PlayerPosition::DEFENDER) => 6.43,
        Str::lower(PlayerPosition::GOALKEEPER) => 4.38,
      ],
    ];

    RefTeamDefaultProjection::insert($seedData);
  }
}

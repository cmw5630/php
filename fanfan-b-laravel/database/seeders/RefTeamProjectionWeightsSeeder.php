<?php

namespace Database\Seeders;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Models\meta\RefTeamProjectionWeight;
use Illuminate\Database\Seeder;
use Str;

class RefTeamProjectionWeightsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (RefTeamProjectionWeight::all()->isNotEmpty()) {
      RefTeamProjectionWeight::truncate();
    }

    $seedData = [
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'a3nyxabgsqlnqfkeg41m6tnpp',  // 맨시티
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'c8h9bw1l82s06h77xxrelzhur',  // 리버풀
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.915,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '9q0arba2kbnywth8bkxlhgmdr',  // 첼시
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.048,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '22doj4sgsocqpxw45h607udje',  // 토트넘
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.022,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.979,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.934,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '4dsgumo7d4zupm2ugsvm4zm4d',  // 아스날
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.943,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '6eqit8ye8aomdsrrq0hk3v7gh',  // 맨유
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.123,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.940,
        Str::lower(PlayerPosition::DEFENDER) => 0.949,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '4txjdaqveermfryvbfrr4taf7',  // 웨스트햄
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.967,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.019,
        Str::lower(PlayerPosition::DEFENDER) => 1.107,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.036,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => 'avxknfz4f6ob0rv9dbnxdzde0',  // 레스터시티(2부?)
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.881,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.012,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.028,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 0.902,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'e5p0ehyguld7egzhiedpdnc3w',  // 브라이튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.017,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'b9si1jn1lfxfund69e9ogcu2n',  // 울버햄튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.123,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.965,
        Str::lower(PlayerPosition::DEFENDER) => 1.068,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.035,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '7vn2i2kd35zuetw6b38gw9jsz',  // 뉴캐슬
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.895,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.947,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.943,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1c8m2ko0wxq1asfkuykurdr0y',  //크리스탈팰리스
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.894,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.014,
        Str::lower(PlayerPosition::DEFENDER) => 1.021,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.991,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '7yx5dqhhphyvfisohikodajhv',  // 브렌트포드
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.008,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.995,
        Str::lower(PlayerPosition::DEFENDER) => 0.989,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.981,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'b496gs285it6bheuikox6z9mj',  // 아스톤빌라
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.903,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.953,
        Str::lower(PlayerPosition::DEFENDER) => 0.952,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.990,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => 'd5ydtvt96bv7fq04yqm2w2632',  // 사우스햄튼
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.958,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.056,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.083,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.089,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'ehd2iemqmschhj2ec0vayztzz',  // 에버튼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.090,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.122,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => '48gk2hpqtsl6p9sx9kjhaydq4',  // 리즈유나이티드 (2부?)
      //   'team_side' => ScheduleWinnerStatus::AWAY,
      //   Str::lower(PlayerPosition::ATTACKER) => 1.026,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.058,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.119,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'hzqh7z0mdl3v7gwete66syxp', // 풀럼
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 0.929,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.019,
        Str::lower(PlayerPosition::DEFENDER) => 0.912,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.943,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1pse9ta7a45pi2w2grjim70ge',  // 본머스
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.064,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.121,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.943,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1qtaiy11gswx327s0vkibf70n',  // 노팅엄
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.106,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.122,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '64bxxwu2mv2qqlv0monbkj1om',  // 번리
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.123,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.122,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'bws31egwjda253q9lvykgnivo',  // 쉐필드
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.123,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.122,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'aksa492u5hf93giwcn2zt1nzz',  // 루턴타운
        'team_side' => ScheduleWinnerStatus::AWAY,
        Str::lower(PlayerPosition::ATTACKER) => 1.123,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.045,
        Str::lower(PlayerPosition::DEFENDER) => 1.122,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.053,
      ],
      // HOME
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'a3nyxabgsqlnqfkeg41m6tnpp',  // 맨시티
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'c8h9bw1l82s06h77xxrelzhur',  // 리버풀
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.987,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '9q0arba2kbnywth8bkxlhgmdr',  // 첼시
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.924,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.910,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.048,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '22doj4sgsocqpxw45h607udje',  // 토트넘
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.890,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.966,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.006,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '4dsgumo7d4zupm2ugsvm4zm4d',  // 아스날
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '6eqit8ye8aomdsrrq0hk3v7gh',  // 맨유
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.909,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.941,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.019,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '4txjdaqveermfryvbfrr4taf7',  // 웨스트햄
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.985,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.941,
        Str::lower(PlayerPosition::DEFENDER) => 1.026,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => 'avxknfz4f6ob0rv9dbnxdzde0',  // 레스터시티(2부?)
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.881,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.012,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.028,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 0.902,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'e5p0ehyguld7egzhiedpdnc3w',  // 브라이튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.039,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.916,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'b9si1jn1lfxfund69e9ogcu2n',  // 울버햄튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.030,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.992,
        Str::lower(PlayerPosition::DEFENDER) => 1.102,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.052,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '7vn2i2kd35zuetw6b38gw9jsz',  // 뉴캐슬
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.901,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.976,
        Str::lower(PlayerPosition::DEFENDER) => 0.940,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1c8m2ko0wxq1asfkuykurdr0y',  // 크리스탈팰리스
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.980,
        Str::lower(PlayerPosition::DEFENDER) => 1.096,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.975,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '7yx5dqhhphyvfisohikodajhv',  // 브렌트포드
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.906,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.972,
        Str::lower(PlayerPosition::DEFENDER) => 1.076,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.962,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'b496gs285it6bheuikox6z9mj',  // 아스톤빌라
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.938,
        Str::lower(PlayerPosition::DEFENDER) => 0.973,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => 'd5ydtvt96bv7fq04yqm2w2632',  // 사우스햄튼
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 0.958,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.056,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.083,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.089,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'ehd2iemqmschhj2ec0vayztzz',  // 에버튼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.018,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.010,
        Str::lower(PlayerPosition::DEFENDER) => 1.052,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.078,
      ],
      // [
      //   'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
      //   'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      //   'vs_team_id' => '48gk2hpqtsl6p9sx9kjhaydq4',  // 리즈유나이티드 (2부?)
      //   'team_side' => ScheduleWinnerStatus::HOME,
      //   Str::lower(PlayerPosition::ATTACKER) => 1.026,
      //   Str::lower(PlayerPosition::MIDFIELDER) => 1.058,
      //   Str::lower(PlayerPosition::DEFENDER) => 1.119,
      //   Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      // ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'hzqh7z0mdl3v7gwete66syxp', // 풀럼
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.058,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.026,
        Str::lower(PlayerPosition::DEFENDER) => 0.908,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1pse9ta7a45pi2w2grjim70ge',  // 본머스
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.058,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.040,
        Str::lower(PlayerPosition::DEFENDER) => 1.102,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.960,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '1qtaiy11gswx327s0vkibf70n',  // 노팅엄
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 0.888,
        Str::lower(PlayerPosition::MIDFIELDER) => 0.994,
        Str::lower(PlayerPosition::DEFENDER) => 0.902,
        Str::lower(PlayerPosition::GOALKEEPER) => 0.952,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => '64bxxwu2mv2qqlv0monbkj1om',  // 번리
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.058,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.040,
        Str::lower(PlayerPosition::DEFENDER) => 1.102,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'bws31egwjda253q9lvykgnivo',  // 쉐필드
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.058,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.040,
        Str::lower(PlayerPosition::DEFENDER) => 1.102,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      ],
      [
        'league_id' => '2kwbbcootiqqgmrzs6o5inle5',
        'season_id' => '1jt5mxgn4q5r6mknmlqv5qjh0',
        'vs_team_id' => 'aksa492u5hf93giwcn2zt1nzz',  // 루턴타운
        'team_side' => ScheduleWinnerStatus::HOME,
        Str::lower(PlayerPosition::ATTACKER) => 1.058,
        Str::lower(PlayerPosition::MIDFIELDER) => 1.040,
        Str::lower(PlayerPosition::DEFENDER) => 1.102,
        Str::lower(PlayerPosition::GOALKEEPER) => 1.083,
      ],
    ];

    RefTeamProjectionWeight::insert($seedData);
  }
}

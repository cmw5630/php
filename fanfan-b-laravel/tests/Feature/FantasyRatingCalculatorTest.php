<?php

namespace Tests\Feature;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Libraries\Classes\FantasyCalculator;
use Artisan;
use SplFileObject;
use Str;
use Tests\TestCase;

class FantasyRatingCalculatorTest extends TestCase
{
  // use RefreshDatabase;
  /**
   * A basic test example.
   *
   * @return void
   */

  public $example = [];

  protected static $isDbInit = false;

  protected static function initDB()
  {
    Artisan::call('migrate');
    Artisan::call('db:seed');
    // Artisan::call('pullopta');
  }

  protected function setUp(): void
  {
    parent::setup();

    // shion 환경
    // if (env('DB_DATABASE') !== 'testing_soccer_api' || env('DB_DATA_DATABASE') !== 'testing_soccer_data') {
    //   dd('안전한 테스트를 위해 테이트 데이터베이스에 대한 제한을 걸어놨습니다. 현재 환경설정이 테스트 데이터베이스가 아닙니다. 조건을 봐주세요.');
    // }

    // if (!static::$isDbInit) {
    //   static::$isDbInit = true;
    //   static::initDB();
    // }

    $this->example = [
      // 일반, 공격 ->
      'mins_played' => 0,                  // 출전시간
      'touches' => 0,                        // 볼 터치
      'unsuccessful_touch' => 0,          // 터치미스
      'own_goals' => 0,                   // 자책골
      'yellow_card' => 0,                 // 경고
      'red_card' => 0,                    // 퇴장
      'goals' => 0, // 득점
      'att_freekick_goal' => 0,            // 프리킥 득점
      'goal_assist' => 0,                  // 어시스트
      'total_scoring_att' => 0,           // 슈팅
      'ontarget_scoring_att' => 0,         // 유효슈팅
      'hit_woodwork' => 0,                // 골대 맞은 슈팅
      'shot_off_target' => 0,              // 골문 벗어난 슈팅
      'blocked_scoring_att' => 0,         // 차단된 슈팅
      'penalty_won' => 0,                  // PK 획득
      'won_contest' => 0,                  // 드리블 성공
      'total_offside' => 0,               // 오프사이드
      'dispossessed' => 0,                // 볼뺏김
      'big_chance_missed' => 0,           // 빅찬스 미스
      'att_pen_miss' => 0,
      'att_pen_post' => 0,
      'att_pen_target' => 0,                // PK 실축

      // 패싱, 수비 -> 
      'accurate_pass' => 0,                  // 패스 성공
      'total_pass' => 0,
      'accurate_long_balls' => 0,            // 롱패스 성공
      'final_third_entries' => 0,            // 파이털써드 침투 패스 성공
      'accurate_cross' => 0,               // 크로스 성공
      'accurate_corners_intobox' => 0,     // 박스 내 투입 성공 코너킥
      'accurate_layoffs' => 0,             // 레이오프 성공
      'accurate_through_ball' => 0,        // 스루 패스 성공 
      'total_att_assist' => 0,          // 키패스
      'big_chance_created' => 0,           // 빅찬스 생성 패스
      'accurate_pull_back' => 0,           // 풀백패스 성공
      'clean_sheet' => 0,
      'goals_conceded' => 0,                 // 실점
      'penalty_conceded' => 0,            // PK 허용
      'challenge_lost' => 0,              // 드리블 돌파 허용
      'effective_clearance' => 0,          // 클리어링 
      'effective_head_clearance' => 0,     // 헤더 클리어링 
      'won_tackle' => 0,                   // 태클 성공
      'clearance_off_line' => 0,           // 골라인 직전 걷어내기
      'interception' => 0,                 // 인터셉트
      'last_man_tackle' => 0,              // 최종 수비수 태클 성공
      'outfielder_block' => 0,             // 슈팅 차단 
      'offside_provoked' => 0,             // 오프사이드 유도 

      // 경합 ->
      'duel_won' => 0,
      'aerial_won' => 0,                     // 공중볼 경합
      'ball_recovery' => 0,                  // 볼 리커버리 성공
      'was_fouled' => 0,                   // 피파울
      'fouled_final_third' => 0,           // 파이널써드 지역 피파울
      'fouls' => -0,                       // 파울
      'duel_lost' => 0,
      'duel_lost-aerial_lost' => 0,                 // 지상 경합 실패
      'aerial_lost' => 0,                 // 공중볼 경합 실패
      'error_lead_to_shot' => 0,          // 슈팅으로 연결된 실책
      'error_lead_to_goal' => 0,          // 실점으로 연결된 실책

      // 골키핑 -> 
      'saved_ibox' => 0,                   // 박스 내 선방
      'penalty_save' => 0,                 // PK 선방
      'dive_catch' => 0,                   // 다이빙 캐치
      'dive_save' => 0,                    // 다이빙 선방
      'accurate_keeper_sweeper' => 0,      // 스위핑 성공
      'punches' => 0,                      // 펀칭
      'accurate_keeper_throws' => 0,       // 던지기 성공
      'good_high_claim' => 0,              // 공중볼 처리 성공
      'cross_not_claimed' => -0,           // 공중볼 처리 실패
      'accurate_goal_kicks' => 0,          // 골킥 성공
      'gk_smother' => 0,                   // 스무더 성공
      'saves' => 0,                        // 선방

      //
      'game_started' => 1,
    ];
  }

  protected function tearDown(): void
  {
    // do something
    parent::tearDown();
  }

  // protected function refreshTestDatabase()
  // {
  //   if (!RefreshDatabaseState::$migrated) {
  //     $this->artisan('migrate:refresh');

  //     $this->app[Kernel::class]->setArtisan(null);

  //     RefreshDatabaseState::$migrated = true;
  //   }

  //   $this->beginDatabaseTransaction();
  // }

  public function simple_weight_helper($_colvalues, $_expected): void
  {
    // [Attacker, Midfielder, Midfielder, Midfielder, Defender, ]
    foreach ($_colvalues as $col => $value) {
      $this->example[$col] = $value;
    }
    $this->example['sub_position'] = null;
    $calculator = new FantasyCalculator(FantasyCalculatorType::FANTASY_RATING, 0);
    if (isset($_expected['all'])) {
      $_expected['all'] += 6;
      if ($_expected['all'] < 2) {
        $_expected['all'] = 2;
      } else if ($_expected['all'] > 10) {
        $_expected['all'] = 10;
      }
      $this->example[$col] = $value;

      $_expected['a']
        = $_expected['am']
        = $_expected['m']
        = $_expected['dm']
        = $_expected['d']
        = $_expected['wb']
        = $_expected['g']
        = $_expected['s_a']
        = $_expected['s_m']
        = $_expected['s_d']
        = $_expected['s_g']
        = $_expected['all'];
    } else {
      foreach ($_expected as $key => $value) {
        $_expected[$key] += 6;
        if ($_expected[$key] < 2) {
          $_expected[$key] = 2;
        } else if ($_expected[$key] > 10) {
          $_expected[$key] = 10;
        }
      }
    }

    $expected = [];
    foreach ($_expected as $k => $v) {
      // $expected[$k] = ((int)($v * 10)) / 10;
      $expected[$k] = __setDecimal($v, 1, 'floor');
    }

    $this->example['position'] = PlayerDailyPosition::STRIKER;
    $this->assertEquals($expected['a'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::ATTACKING_MIDFIELDER;
    $this->assertEquals($expected['am'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::MIDFIELDER;
    $this->assertEquals($expected['m'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::DEFENSIVE_MIDFIELDER;
    $this->assertEquals($expected['dm'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::DEFENDER;
    $this->assertEquals($expected['d'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::WING_BACK;
    $this->assertEquals($expected['wb'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::GOALKEEPER;
    $this->assertEquals($expected['g'], $calculator->calculate($this->example));

    $this->example['position'] = PlayerDailyPosition::SUBSTITUTE;

    $this->example['sub_position'] = PlayerPosition::ATTACKER;
    $this->assertEquals($expected['s_a'], $calculator->calculate($this->example));

    $this->example['sub_position'] = PlayerPosition::MIDFIELDER;
    $this->assertEquals($expected['s_m'], $calculator->calculate($this->example));

    $this->example['sub_position'] = PlayerPosition::DEFENDER;
    $this->assertEquals($expected['s_d'], $calculator->calculate($this->example));

    $this->example['sub_position'] = PlayerPosition::GOALKEEPER;
    $this->assertEquals($expected['s_g'], $calculator->calculate($this->example));
  }

  public function test_goals(): void
  {
    $this->simple_weight_helper(['goals' => 1], ['all' => 0.9913]);
    $this->simple_weight_helper(['goals' => 2], ['all' => 0.9595 * 2]);
    $this->simple_weight_helper(['goals' => 3], ['all' => 0.9231 * 3]);
    $this->simple_weight_helper(['goals' => 4], ['all' => 0.8789 * 4]);
    for ($i = 5; $i < 10; $i++) {
      $this->simple_weight_helper(['goals' => $i], ['all' => 0.8575 * $i]);
    }
  }

  public function test_goals_conceded(): void
  {
    $this->simple_weight_helper(['goals_conceded' => 0, 'clean_sheet' => 1], ['a' => 0.0451, 'am' => 0.0587, 'm' => 0.0821, 'dm' => 0.1188, 'd' => 0.2895, 'wb' => 0.2612, 'g' => 0.4282, 's_a' => 0.0451, 's_m' => 0.0587, 's_d' => 0.2791, 's_g' => 0.4282]);
    $this->simple_weight_helper(['goals_conceded' => 1, 'clean_sheet' => 0], ['a' => -0.0225, 'am' => -0.0295, 'm' => -0.0535, 'dm' => -0.0585, 'd' => -0.0695, 'wb' => -0.0695, 'g' => -0.1595, 's_a' => -0.0205, 's_m' => -0.0435, 's_d' => -0.0695, 's_g' => -0.1195]);
    $this->simple_weight_helper(['goals_conceded' => 2, 'clean_sheet' => 0], ['a' => -0.0225 * 2, 'am' => -0.0295 * 2, 'm' => -0.0675 * 2, 'dm' => -0.0735 * 2, 'd' => -0.0815 * 2, 'wb' => -0.0815 * 2, 'g' => -0.1995 * 2, 's_a' => -0.0205 * 2, 's_m' => -0.0675 * 2, 's_d' => -0.0815 * 2, 's_g' => -0.1595 * 2]);
    $this->simple_weight_helper(['goals_conceded' => 3, 'clean_sheet' => 0], ['a' => -0.0225 * 3, 'am' => -0.0295 * 3, 'm' => -0.0721 * 3, 'dm' => -0.0915 * 3, 'd' => -0.1355 * 3, 'wb' => -0.1355 * 3, 'g' => -0.3215 * 3, 's_a' => -0.0205 * 3, 's_m' => -0.0911 * 3, 's_d' => -0.1355 * 3, 's_g' => -0.2715 * 3]);
    $this->simple_weight_helper(['goals_conceded' => 4, 'clean_sheet' => 0], ['a' => -0.0225 * 4, 'am' => -0.0295 * 4, 'm' => -0.0875 * 4, 'dm' => -0.1005 * 4, 'd' => -0.1995 * 4, 'wb' => -0.1995 * 4, 'g' => -0.4015 * 4, 's_a' => -0.0205 * 4, 's_m' => -0.1475 * 4, 's_d' => -0.1995 * 4, 's_g' => -0.3415 * 4]);
    $this->simple_weight_helper(['goals_conceded' => 5, 'clean_sheet' => 0], ['a' => -0.0225 * 5, 'am' => -0.0295 * 5, 'm' => -0.0875 * 5, 'dm' => -0.1005 * 5, 'd' => -0.1995 * 5, 'wb' => -0.1995 * 5, 'g' => -0.4015 * 5, 's_a' => -0.0205 * 5, 's_m' => -0.1475 * 5, 's_d' => -0.1995 * 5, 's_g' => -0.3415 * 5]);
  }

  public function test_duel_won__aerial_won(): void
  {
    // 주의: duel_won__aerial_won, aerial_won, duel_lost-aerial_lost, duel_lost-aerial_lost ,aerial_lost 특성에 대해서도 가중치 같이 계산

    // 지상경합(4) 성공비율:0.8, 공중볼 경합 성공비율: 0.5
    $this->simple_weight_helper(
      [
        'duel_won' => 5, 'aerial_won' => 1, 'duel_lost' => 2, 'aerial_lost' => 1
      ],
      [
        'a' => 0.0715 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'am' => 0.0715 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'm' => 0.0735 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'dm' => 0.0735 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'd' => 0.0725 * 4 + 0.0361 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'wb' => 0.0725 * 4 + 0.0361 * 1 - 0.0324 * 1 - 0.0314 * 1,
        'g' => 0.0412 * 4 + 0.0412 * 1 - 0.0324 * 1 - 0.0314 * 1,
        's_a' => 0.0715 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        's_m' => 0.0735 * 4 + 0.0361 * 1 - 0.0324 * 1 - 0.0314 * 1,
        's_d' => 0.0725 * 4 + 0.0362 * 1 - 0.0324 * 1 - 0.0314 * 1,
        's_g' => 0.0412 * 4 + 0.0412 * 1 - 0.0324 * 1 - 0.0314 * 1,
      ]
    );

    // 지상경합(3) 성공비율:0.3333, 공중볼경합(5) 성공비율: 0.833333
    $this->simple_weight_helper(
      [
        'duel_won' => 8, 'aerial_won' => 5, 'duel_lost' => 7, 'aerial_lost' => 1
      ],
      [
        'a' => 0.0399 * 3 + 0.0705 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'am' => 0.0362 * 3 + 0.0695 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'm' => 0.0362 * 3 + 0.0695 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'dm' => 0.0362 * 3 + 0.0711 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'd' => 0.0431 * 3 + 0.0701 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'wb' => 0.0431 * 3 + 0.0701 * 5 - 0.0324 * 6 - 0.0314 * 1,
        'g' => 0.0412 * 3 + 0.0412 * 5 - 0.0324 * 6 - 0.0314 * 1,
        's_a' => 0.0399 * 3 + 0.0705 * 5 - 0.0324 * 6 - 0.0314 * 1,
        's_m' => 0.0412 * 3 + 0.0695 * 5 - 0.0324 * 6 - 0.0314 * 1,
        's_d' => 0.0431 * 3 + 0.0701 * 5 - 0.0324 * 6 - 0.0314 * 1,
        's_g' => 0.0412 * 3 + 0.0412 * 5 - 0.0324 * 6 - 0.0314 * 1,
      ]
    );

    // 지상경합(4) 성공비율:0.6666, 공중볼 경합(1) 성공비율: 0.5555
    $this->simple_weight_helper(
      [
        'duel_won' => 5, 'aerial_won' => 1, 'duel_lost' => 3, 'aerial_lost' => 1
      ],
      [
        'a' => 0.0399 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'am' => 0.0399 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'm' => 0.0452 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'dm' => 0.0442 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'd' => 0.0431 * 4 + 0.0361 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'wb' => 0.0431 * 4 + 0.0361 * 1 - 0.0324 * 2 - 0.0314 * 1,
        'g' => 0.0412 * 4 + 0.0412 * 1 - 0.0324 * 2 - 0.0314 * 1,
        's_a' => 0.0399 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        's_m' => 0.0412 * 4 + 0.0361 * 1 - 0.0324 * 2 - 0.0314 * 1,
        's_d' => 0.0431 * 4 + 0.0362 * 1 - 0.0324 * 2 - 0.0314 * 1,
        's_g' => 0.0412 * 4 + 0.0412 * 1 - 0.0324 * 2 - 0.0314 * 1,
      ]
    );

    // 지상경합(3) 성공비율:1.0, 공중볼 경합 성공비율: 0.857142...
    $this->simple_weight_helper(
      [
        'duel_won' => 9, 'aerial_won' => 6, 'duel_lost' => 1, 'aerial_lost' => 1
      ],
      [
        'a' => 0.0715 * 3 + 0.0705 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'am' => 0.0362 * 3 + 0.0695 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'm' => 0.0362 * 3 + 0.0695 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'dm' => 0.0362 * 3 + 0.0711 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'd' => 0.0725 * 3 + 0.0701 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'wb' => 0.0725 * 3 + 0.0701 * 6 - 0.0324 * 0 - 0.0314 * 1,
        'g' => 0.0412 * 3 + 0.0412 * 6 - 0.0324 * 0 - 0.0314 * 1,
        's_a' => 0.0715 * 3 + 0.0705 * 6 - 0.0324 * 0 - 0.0314 * 1,
        's_m' => 0.0735 * 3 + 0.0695 * 6 - 0.0324 * 0 - 0.0314 * 1,
        's_d' => 0.0725 * 3 + 0.0701 * 6 - 0.0324 * 0 - 0.0314 * 1,
        's_g' => 0.0412 * 3 + 0.0412 * 6 - 0.0324 * 0 - 0.0314 * 1,
      ]
    );

    // 지상경합(2) 성공비율:1.0, 공중볼 경합(7) 성공비율: 0.7777777778...
    $this->simple_weight_helper(
      [
        'duel_won' => 9, 'aerial_won' => 7, 'duel_lost' => 2, 'aerial_lost' => 2
      ],
      [
        'a' => 0.0362 * 2 + 0.0405 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'am' => 0.0362 * 2 + 0.0385 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'm' => 0.0362 * 2 + 0.0385 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'dm' => 0.0362 * 2 + 0.0401 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'd' => 0.0362 * 2 + 0.0412 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'wb' => 0.0362 * 2 + 0.0412 * 7 - 0.0324 * 0 - 0.0314 * 2,
        'g' => 0.0412 * 2 + 0.0412 * 7 - 0.0324 * 0 - 0.0314 * 2,
        's_a' => 0.0362 * 2 + 0.0405 * 7 - 0.0324 * 0 - 0.0314 * 2,
        's_m' => 0.0362 * 2 + 0.0385 * 7 - 0.0324 * 0 - 0.0314 * 2,
        's_d' => 0.0362 * 2 + 0.0401 * 7 - 0.0324 * 0 - 0.0314 * 2,
        's_g' => 0.0412 * 2 + 0.0412 * 7 - 0.0324 * 0 - 0.0314 * 2,
      ]
    );
    // $this->simple_weight_helper(['duel_won' => 1, 'aerial_won' => 0], ['a' => 0.0362, 'am' => 0.0587, 'm' => 0.0821, 'dm' => 0.1188, 'd' => 0.2895, 'wb' => 0.2612, 'g' => 0.4282, 's_a' => 0.0451, 's_m' => 0.0587, 's_d' => 0.2791, 's_g' => 0.4282]);
  }

  public function test_mins_played()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['mins_played' => $i], ['all' => 0.0015 * $i]);
    }
  }

  public function test_touches()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['touches' => $i], ['a' => 0.0027 * $i, 'am' => 0.0029 * $i, 'm' => 0.0032 * $i, 'dm' => 0.0032 * $i, 'd' => 0.0032 * $i, 'wb' => 0.0032 * $i, 'g' => 0.0037 * $i, 's_a' => 0.0027 * $i, 's_m' => 0.0032 * $i, 's_d' => 0.0032 * $i, 's_g' => 0.0037 * $i]);
    }
  }

  public function test_unsuccessful_touch()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['unsuccessful_touch' => $i], ['all' => -0.0118 * $i]);
    }
  }

  public function test_own_goals()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['own_goals' => $i], ['all' => -0.9257 * $i]);
    }
  }

  public function test_yellow_card()
  {
    $this->simple_weight_helper(['yellow_card' => 0, 'second_yellow' => 0], ['all' => 0]);
    $this->simple_weight_helper(['yellow_card' => 1, 'second_yellow' => 0], ['all' => -0.1115 * 1]);
    $this->simple_weight_helper(['yellow_card' => 1, 'second_yellow' => 1], ['all' => -0.1115 * 2]);
  }

  public function test_red_card()
  {
    $this->simple_weight_helper(['yellow_card' => 0, 'second_yellow' => 0, 'red_card' => 1], ['all' => -0.9375 * 0 + -1.0752]);
    $this->simple_weight_helper(['yellow_card' => 1, 'second_yellow' => 0, 'red_card' => 1], ['all' => -0.1115 * 1 + -1.0752]);
    $this->simple_weight_helper(['yellow_card' => 1, 'second_yellow' => 1, 'red_card' => 1], ['all' => -0.1115 * 2 + -0.9375]);
  }

  public function test_att_freekick_goal()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['att_freekick_goal' => $i], ['all' => 0.0131 * $i]);
    }
  }

  public function test_goal_assist()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['goal_assist' => $i], ['all' => 0.7921 * $i]);
    }
  }

  public function test_total_scoring_att()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['total_scoring_att' => $i], ['all' => 0.0257 * $i]);
    }
  }

  public function test_ontarget_scoring_att()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['ontarget_scoring_att' => $i], ['all' => 0.0705 * $i]);
    }
  }

  public function test_hit_woodwork()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['hit_woodwork' => $i], ['all' => 0.0651 * $i]);
    }
  }

  public function test_shot_off_target()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['shot_off_target' => $i], ['all' => 0.0151 * $i]);
    }
  }

  public function test_blocked_scoring_att()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['blocked_scoring_att' => $i], ['all' => 0.0477 * $i]);
    }
  }

  public function test_penalty_won()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['penalty_won' => $i], ['all' => 0.4991 * $i]);
    }
  }

  public function test_won_contest()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['won_contest' => $i], ['all' => 0.0547 * $i]);
    }
  }

  public function test_total_offside()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['total_offside' => $i], ['all' => -0.0108 * $i]);
    }
  }

  public function test_dispossessed()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['dispossessed' => $i], ['all' => -0.0351 * $i]);
    }
  }

  public function test_big_chance_missed()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['big_chance_missed' => $i], ['all' => -0.0691 * $i]);
    }
  }

  // PK 실축
  public function test_att_pen_miss_post_target()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper([
        'att_pen_miss' => $i,
        'att_pen_post' => $i,
        'att_pen_target' => $i,
      ], ['all' => $i * 3 * -0.4289]);
    }
  }

  // 패싱, 수비
  // 패스 성공
  public function test_accurate_cross()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_cross' => $i], ['all' => 0.0485 * $i]);
    }
  }

  public function test_accurate_corners_intobox()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_corners_intobox' => $i], ['all' => 0.0411 * $i]);
    }
  }

  public function test_accurate_layoffs()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_layoffs' => $i], ['all' => 0.0255 * $i]);
    }
  }

  public function test_accurate_through_ball()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_through_ball' => $i], ['all' => 0.0415 * $i]);
    }
  }

  public function test_total_att_assist()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['total_att_assist' => $i], ['all' => 0.0869 * $i]);
    }
  }

  public function test_big_chance_created()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['big_chance_created' => $i], ['all' => 0.1005 * $i]);
    }
  }

  public function test_accurate_pull_back()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_pull_back' => $i], ['all' => 0.0102 * $i]);
    }
  }


  public function test_penalty_conceded()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['penalty_conceded' => $i], ['all' => -0.4985 * $i]);
    }
  }

  public function test_challenge_lost()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['challenge_lost' => $i], ['all' => -0.0878 * $i]);
    }
  }

  public function test_effective_clearance()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['effective_clearance' => $i], ['all' => 0.0372 * $i]);
    }
  }

  public function test_effective_head_clearance()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['effective_head_clearance' => $i], ['all' => 0.0193 * $i]);
    }
  }

  public function test_won_tackle()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['won_tackle' => $i], ['all' => 0.0666 * $i]);
    }
  }

  public function test_clearance_off_line()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['clearance_off_line' => $i], ['all' => 0.4186 * $i]);
    }
  }

  public function test_interception()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['interception' => $i], ['all' => 0.0605 * $i]);
    }
  }

  public function test_last_man_tackle()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['last_man_tackle' => $i], ['all' => 0.3312 * $i]);
    }
  }

  public function test_outfielder_block()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['outfielder_block' => $i], ['all' => 0.0502 * $i]);
    }
  }

  public function test_offside_provoked()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['offside_provoked' => $i], ['all' => 0.0216 * $i]);
    }
  }

  public function test_blocked_cross()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['blocked_cross' => 1], ['all' => 0.0229 * $i]);
    }
  }

  // 경합
  public function test_was_fouled()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['was_fouled' => $i], ['all' => 0.0051 * $i]);
    }
  }

  public function test_fouled_final_third()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['fouled_final_third' => $i], ['all' => 0.0027 * $i]);
    }
  }

  public function test_fouls()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['fouls' => $i], ['all' => -0.0077 * $i]);
    }
  }


  public function test_error_lead_to_shot()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['error_lead_to_shot' => $i], ['all' => -0.1001 * $i]);
    }
  }

  public function test_error_lead_to_goal()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['error_lead_to_goal' => $i], ['all' => -1.2021 * $i]);
    }
  }

  // 골키핑
  public function test_saved_ibox()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['saved_ibox' => $i], ['all' => 0.0382 * $i]);
    }
  }

  public function test_penalty_save()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['penalty_save' => $i], ['all' => 0.7988 * $i]);
    }
  }

  public function test_dive_catch()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['dive_catch' => $i], ['all' => 0.0282 * $i]);
    }
  }

  public function test_dive_save()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['dive_save' => $i], ['all' => 0.0681 * $i]);
    }
  }

  public function test_accurate_keeper_sweeper()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_keeper_sweeper' => $i], ['all' => 0.0427 * $i]);
    }
  }

  public function test_punches()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['punches' => $i], ['all' => 0.0058 * $i]);
    }
  }


  public function test_accurate_keeper_throws()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_keeper_throws' => $i], ['all' => 0.0249 * $i]);
    }
  }

  public function test_good_high_claim()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['good_high_claim' => $i], ['all' => 0.0723 * $i]);
    }
  }

  public function test_cross_not_claimed()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['cross_not_claimed' => $i], ['all' => -0.1025 * $i]);
    }
  }

  public function test_accurate_goal_kicks()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['accurate_goal_kicks' => $i], ['all' => 0.0090 * $i]);
    }
  }

  public function test_gk_smother()
  {
    for ($i = 1; $i < 4; $i++) {
      $this->simple_weight_helper(['gk_smother' => $i], ['all' => 0.0318 * $i]);
    }
  }

  public function test_saves()
  {

    $this->simple_weight_helper(
      ['saves' => 5, 'saved_ibox' => 1, 'clean_sheet' => 0],
      [
        'a' => 0.0525 * 5 + 0.0382 * 1 + 0.0451 * 0,
        'am' => 0.0525 * 5 + 0.0382 * 1 + 0.0587 * 0,
        'm' => 0.0525 * 5 + 0.0382 * 1 + 0.0821 * 0,
        'dm' => 0.0525 * 5 + 0.0382 * 1 + 0.1188 * 0,
        'd' => 0.0525 * 5 + 0.0382 * 1 + 0.2895 * 0,
        'wb' => 0.0525 * 5 + 0.0382 * 1 + 0.2612 * 0,
        'g' => 0.0525 * 5 + 0.0382 * 1 + 0.4282 * 0,
        's_a' => 0.0525 * 5 + 0.0382 * 1 + 0.0451 * 0,
        's_m' => 0.0525 * 5 + 0.0382 * 1 + 0.0587 * 0,
        's_d' => 0.0525 * 5 + 0.0382 * 1 + 0.02791 * 0,
        's_g' => 0.0525 * 5 + 0.0382 * 1 + 0.4282 * 0,
      ]
    );
    $this->simple_weight_helper(
      ['saves' => 5, 'saved_ibox' => 1, 'clean_sheet' => 1],
      [
        'a' => 0.0525 * 5 + 0.0382 * 1 + 0.0451 * 1,
        'am' => 0.0525 * 5 + 0.0382 * 1 + 0.0587 * 1,
        'm' => 0.0525 * 5 + 0.0382 * 1 + 0.0821 * 1,
        'dm' => 0.0525 * 5 + 0.0382 * 1 + 0.1188 * 1,
        'd' => 0.0525 * 5 + 0.0382 * 1 + 0.2895 * 1,
        'wb' => 0.0525 * 5 + 0.0382 * 1 + 0.2612 * 1,
        'g' => 0.0525 * 5 + 0.0382 * 1 + 0.4282 * 1,
        's_a' => 0.0525 * 5 + 0.0382 * 1 + 0.0451 * 1,
        's_m' => 0.0525 * 5 + 0.0382 * 1 + 0.0587 * 1,
        's_d' => 0.0525 * 5 + 0.0382 * 1 + 0.2791 * 1,
        's_g' => 0.0525 * 5 + 0.0382 * 1 + 0.4282 * 1,
      ]
    );
    $this->simple_weight_helper(
      ['saves' => 5, 'saved_ibox' => 4, 'clean_sheet' => 0],
      [
        'a' => 0.3995 * 5 + 0.0382 * 4 + 0.0451 * 0,
        'am' => 0.3995 * 5 + 0.0382 * 4 + 0.0587 * 0,
        'm' => 0.3995 * 5 + 0.0382 * 4 + 0.0821 * 0,
        'dm' => 0.3995 * 5 + 0.0382 * 4 + 0.1188 * 0,
        'd' => 0.3995 * 5 + 0.0382 * 4 + 0.2895 * 0,
        'wb' => 0.3995 * 5 + 0.0382 * 4 + 0.2612 * 0,
        'g' => 0.3995 * 5 + 0.0382 * 4 + 0.4282 * 0,
        's_a' => 0.3995 * 5 + 0.0382 * 4 + 0.0451 * 0,
        's_m' => 0.3995 * 5 + 0.0382 * 4 + 0.0587 * 0,
        's_d' => 0.3995 * 5 + 0.0382 * 4 + 0.2791 * 0,
        's_g' => 0.3995 * 5 + 0.0382 * 4 + 0.4282 * 0,
      ]
    );
    $this->simple_weight_helper(
      ['saves' => 5, 'saved_ibox' => 4, 'clean_sheet' => 1],
      [
        'a' => 0.4997 * 5 + 0.0382 * 4 + 0.0451 * 1,
        'am' => 0.4997 * 5 + 0.0382 * 4 + 0.0587 * 1,
        'm' => 0.4997 * 5 + 0.0382 * 4 + 0.0821 * 1,
        'dm' => 0.4997 * 5 + 0.0382 * 4 + 0.1188 * 1,
        'd' => 0.4997 * 5 + 0.0382 * 4 + 0.2895 * 1,
        'wb' => 0.4997 * 5 + 0.0382 * 4 + 0.2612 * 1,
        'g' => 0.4997 * 5 + 0.0382 * 4 + 0.4282 * 1,
        's_a' => 0.4997 * 5 + 0.0382 * 4 + 0.0451 * 1,
        's_m' => 0.4997 * 5 + 0.0382 * 4 + 0.0587 * 1,
        's_d' => 0.4997 * 5 + 0.0382 * 4 + 0.2791 * 1,
        's_g' => 0.4997 * 5 + 0.0382 * 4 + 0.4282 * 1,
      ]
    );
  }

  // public function test_sample_data(): void
  // {
  //   $playerStatsGroup = new SplFileObject('./tests/Feature/fantasyRatingSample.csv');
  //   $playerStatsGroup->setFlags(SplFileObject::READ_CSV);

  //   $colNames = [];
  //   foreach ($playerStatsGroup as $idx => $row) {
  //     if ($idx === 0) {
  //       foreach ($row as $col => $value) {
  //         $colNames[$col] = Str::snake($value);
  //       }
  //       continue;
  //     }
  //     if (count($row) === 1 && $row[0] === null) {
  //       break;
  //     }
  //     $playerStats = array_combine($colNames, $row);
  //     $calculator = new FantasyCalculator(FantasyCalculatorType::FANTASY_RATING, 0);
  //     // logger('POINT:' . $playerStats['p_o_i_n_t_s']);
  //     // logger('CAL_FANTASY_POINT:' . $calculator->getFantasyPoint($playerStats));
  //     // dd($calculator->calculate($playerStats));
  //     // $calculator->calculate($playerStats);
  //     // $this->assertEquals($playerStats['p_o_i_n_t_s'], $calculator->calculate($playerStats));
  //   }
  // }
}

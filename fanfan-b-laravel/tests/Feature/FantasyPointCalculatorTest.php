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

class FantasyPointCalculatorTest extends TestCase
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
      'goals' => 0,
      'winning_goal' => 0,
      'att_freekick_goal' => 0,
      'ontarget_scoring_att' => 0,
      'won_contest' => 0,
      'big_chance_missed' => 0,
      'att_pen_miss' => 0,
      'att_pen_post' => 0,
      'att_pen_target' => 0,
      'total_offside' => 0,
      'goal_assist' => 0,
      'total_att_assist' => 0,
      'big_chance_created' => 0,
      'final_third_entries' => 0,
      'accurate_cross' => 0,
      'accurate_long_balls' => 0,
      'accurate_pass' => 0,
      'total_pass' => 0,
      'won_tackle' => 0,
      'outfielder_block' => 0,
      'effective_clearance' => 0,
      'offside_provoked' => 0,
      'fouls' => 0,
      'penalty_conceded' => 0,
      'clean_sheet' => 0,
      'goals_conceded' => 0,
      'error_lead_to_shot' => 0,
      'error_lead_to_goal' => 0,
      'ball_recovery' => 0,
      'interception' => 0,
      'penalty_won' => 0,
      'duel_won' => 0,
      'duel_lost' => 0,
      'aerial_won' => 0,
      'aerial_lost' => 0,
      'saves' => 0,
      'saved_ibox' => 0,
      'penalty_save' => 0,
      'good_high_claim' => 0,
      'dive_catch' => 0,
      'punches' => 0,
      'accurate_keeper_sweeper' => 0,
      'mins_played' => 0,
      'own_goals' => 0,
      'yellow_card' => 0,
      'red_card' => 0,
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
    $calculator = new FantasyCalculator(FantasyCalculatorType::FANTASY_POINT, 0);
    if (isset($_expected['all'])) {
      $_expected['a'] = $_expected['m'] = $_expected['d'] = $_expected['g'] = $_expected['all'];
    }

    $this->example['position'] = PlayerDailyPosition::STRIKER;
    $this->assertEquals($_expected['a'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::ATTACKING_MIDFIELDER;
    $this->assertEquals($_expected['m'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::MIDFIELDER;
    $this->assertEquals($_expected['m'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::DEFENSIVE_MIDFIELDER;
    $this->assertEquals($_expected['m'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::DEFENDER;
    $this->assertEquals($_expected['d'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::WING_BACK;
    $this->assertEquals($_expected['d'], $calculator->calculate($this->example));
    $this->example['position'] = PlayerDailyPosition::GOALKEEPER;
    $this->assertEquals($_expected['g'], $calculator->calculate($this->example));

    $this->example['position'] = PlayerDailyPosition::SUBSTITUTE;

    $this->example['sub_position'] = PlayerPosition::ATTACKER;
    $this->assertEquals($_expected['a'], $calculator->calculate($this->example));

    $this->example['sub_position'] = PlayerPosition::MIDFIELDER;
    $this->assertEquals($_expected['m'], $calculator->calculate($this->example));

    $this->example['sub_position'] = PlayerPosition::DEFENDER;
    $this->assertEquals($_expected['d'], $calculator->calculate($this->example),  0.00000001);

    $this->example['sub_position'] = PlayerPosition::GOALKEEPER;
    $this->assertEquals($_expected['g'], $calculator->calculate($this->example));
  }

  public function test_goals(): void
  {
    $this->simple_weight_helper(['goals' => 1], ['a' => 10, 'm' => 10, 'd' => 12, 'g' => 12]);
    $this->simple_weight_helper(['goals' => 2], ['a' => 20, 'm' => 20, 'd' => 24, 'g' => 24]);
    $this->simple_weight_helper(['goals' => 3], ['a' => 40, 'm' => 40, 'd' => 48, 'g' => 48]);
    $this->simple_weight_helper(['goals' => 4], ['a' => 50, 'm' => 50, 'd' => 60, 'g' => 60]);
    $this->simple_weight_helper(['goals' => 5], ['a' => 50, 'm' => 50, 'd' => 60, 'g' => 60]);
    $this->simple_weight_helper(['goals' => 100], ['a' => 50, 'm' => 50, 'd' => 60, 'g' => 60]);
  }

  public function test_winning_goals()
  {
    $this->simple_weight_helper(['winning_goal' => 1], ['all' => 3]);
  }

  public function test_att_freekick_goal()
  {
    $this->simple_weight_helper(['att_freekick_goal' => 1], ['all' => 2]);
  }

  public function test_ontarget_scoring_att()
  {
    $this->simple_weight_helper(['ontarget_scoring_att' => 1], ['a' => 1.5, 'm' => 1.5, 'd' => 1.8, 'g' => 2]);
    $this->simple_weight_helper(['ontarget_scoring_att' => 3], ['a' => 4.5, 'm' => 4.5, 'd' => 5.4, 'g' => 6]);
  }

  public function test_won_contest()
  {
    $this->simple_weight_helper(['won_contest' => 1], ['a' => 1, 'm' => 1, 'd' => 1, 'g' => 1.2]);
  }

  public function test_goal_assist()
  {
    $this->simple_weight_helper(['goal_assist' => 1], ['all' => 8]);
  }

  public function test_big_chance_missed()
  {
    $this->simple_weight_helper(['big_chance_missed' => 1], ['all' => -1]);
  }

  public function test_att_pen_miss__att_pen_post__att_pen_target()
  {
    $this->simple_weight_helper(['att_pen_miss' => 1, 'att_pen_post' => 0, 'att_pen_target' => 3], ['all' => -12]);
    $this->simple_weight_helper(['att_pen_miss' => 1, 'att_pen_post' => 2, 'att_pen_target' => 0], ['all' => -9]);
    $this->simple_weight_helper(['att_pen_miss' => 1, 'att_pen_post' => 1, 'att_pen_target' => 1], ['all' => -9]);
    $this->simple_weight_helper(['att_pen_miss' => 2, 'att_pen_post' => 1, 'att_pen_target' => 1], ['all' => -12]);
  }

  public function test_total_offside()
  {
    $this->simple_weight_helper(['total_offside' => 1], ['a' => -0.3, 'm' => -0.3, 'd' => -0.3, 'g' => 0]);
  }


  public function test_total_att_assist()
  {
    $this->simple_weight_helper(['total_att_assist' => 1], ['a' => 2, 'm' => 2, 'd' => 2.3, 'g' => 2.5]);
    $this->simple_weight_helper(['total_att_assist' => 3], ['a' => 6, 'm' => 6, 'd' => 6.9, 'g' => 7.5]);
  }

  public function test_big_chance_created()
  {
    $this->simple_weight_helper(['big_chance_created' => 1], ['all' => 3]);
  }

  public function test_final_third_entries()
  {
    $this->simple_weight_helper(['final_third_entries' => 1], ['a' => 0.1, 'm' => 0.1, 'd' => 0.1, 'g' => 0]);
  }

  public function test_accurate_cross()
  {
    $this->simple_weight_helper(['accurate_cross' => 1], ['a' => 1, 'm' => 1, 'd' => 1, 'g' => 0]);
  }

  public function test_accurate_long_balls()
  {
    $this->simple_weight_helper(['accurate_long_balls' => 1], ['a' => 0.5, 'm' => 0.5, 'd' => 0.5, 'g' => 0.3]);
  }

  public function test_accurate_pass__total_pass()
  {
    $this->simple_weight_helper(['accurate_pass' => 1, 'total_pass' => 100], ['a' => -9.9, 'm' => -19.8, 'd' => -29.7, 'g' => 0]);
  }

  // public function test_total_pass__accurate_pass()
  // {
  //   // 테스트케이스 중복
  //   // test_accurate_pass__total_pass 테스트 케이스 중복
  // }

  public function test_won_tackle()
  {
    $this->simple_weight_helper(['won_tackle' => 1], ['a' => 1.2, 'm' => 1.2, 'd' => 1.4, 'g' => 0]);
  }

  public function test_outfielder_block()
  {
    $this->simple_weight_helper(['outfielder_block' => 1], ['a' => 1.4, 'm' => 1.4, 'd' => 1.6, 'g' => 0]);
  }

  public function test_effective_clearance()
  {
    $this->simple_weight_helper(['effective_clearance' => 1], ['a' => 0.8, 'm' => 0.8, 'd' => 1, 'g' => 0]);
  }

  public function test_offside_provoked()
  {
    $this->simple_weight_helper(['offside_provoked' => 1], ['a' => 1.4, 'm' => 1.5, 'd' => 1.6, 'g' => 0]);
  }

  public function test_fouls()
  {
    $this->simple_weight_helper(['fouls' => 1], ['a' => -0.2, 'm' => -0.2, 'd' => -0.6, 'g' => -1]);
  }

  public function test_penalty_conceded()
  {
    $this->simple_weight_helper(['penalty_conceded' => 1], ['a' => -5, 'm' => -5, 'd' => -5, 'g' => -5]);
  }

  public function test_clean_sheet()
  {
    $this->simple_weight_helper(['clean_sheet' => 1], ['a' => 5, 'm' => 5, 'd' => 5.5, 'g' => 6]);
  }

  public function test_goals_conceded()
  {
    $this->simple_weight_helper(['goals_conceded' => 1], ['a' => 0, 'm' => -0.5, 'd' => -2, 'g' => -3.5]);
  }

  public function test_error_lead_to_shot()
  {
    $this->simple_weight_helper(['error_lead_to_shot' => 1], ['a' => -1.5, 'm' => -1.5, 'd' => -2, 'g' => -2.8]);
  }

  public function test_error_lead_to_goal()
  {
    $this->simple_weight_helper(['error_lead_to_goal' => 1], ['a' => -6, 'm' => -6, 'd' => -6, 'g' => -6]);
  }

  public function test_ball_recovery()
  {
    $this->simple_weight_helper(['ball_recovery' => 1], ['a' => 0.8, 'm' => 0.8, 'd' => 0.8, 'g' => 0.6]);
  }

  public function test_interception()
  {
    $this->simple_weight_helper(['interception' => 1], ['a' => 1, 'm' => 1, 'd' => 1.2, 'g' => 0]);
  }

  public function test_penalty_won()
  {
    $this->simple_weight_helper(['penalty_won' => 1], ['a' => 5, 'm' => 5, 'd' => 5, 'g' => 5]);
  }

  public function test_duel_won()
  {
    $this->simple_weight_helper(['duel_won' => 1], ['a' => 1.4, 'm' => 1.4, 'd' => 1.5, 'g' => 0.8]);
  }

  public function test_duel_lost()
  {
    $this->simple_weight_helper(['duel_lost' => 1], ['a' => -0.4, 'm' => -0.5, 'd' => -0.8, 'g' => -0.4]);
  }

  public function test_duel_won__aerial_won()
  {
    // duel_won >= aerial_won
    $this->simple_weight_helper(['duel_won' => 4, 'aerial_won' => 1], ['a' =>  5.8, 'm' => 5.8, 'd' => 6.2, 'g' => 3.2]);
  }

  public function test_duel_lost__aerial_lost()
  {
    // duel_lost >= aerial_lost
    $this->simple_weight_helper(['duel_lost' => 4, 'aerial_lost' => 1], ['a' =>  -1.6, 'm' => -2, 'd' => -3.3, 'g' => -1.6]);
  }

  // public function test_aerial_won()
  // {
  //   // duel_won >= aerial_won
  //   // 테스트케이스 중복 (test_duel_won__aerial_won)
  // }

  // public function test_aerial_lost()
  // {
  //   // duel_lost >= aerial_lost
  //   // 테스트케이스 중복 (test_duel_lost__aerial_lost)
  // }


  public function test_saves()
  {
    $this->simple_weight_helper(['saves' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 3]);
  }

  public function test_saved_ibox()
  {
    $this->simple_weight_helper(['saved_ibox' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 0.2]);
  }

  public function test_penalty_save()
  {
    $this->simple_weight_helper(['penalty_save' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 7]);
  }

  public function test_good_high_claim()
  {
    $this->simple_weight_helper(['good_high_claim' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 2]);
  }

  public function test_dive_catch()
  {
    $this->simple_weight_helper(['dive_catch' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 2]);
  }

  public function test_punches()
  {
    $this->simple_weight_helper(['punches' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 1]);
  }

  public function test_accurate_keeper_sweeper()
  {
    $this->simple_weight_helper(['accurate_keeper_sweeper' => 1], ['a' => 0, 'm' => 0, 'd' => 0, 'g' => 2]);
  }

  public function test_mins_played(): void
  {
    $this->simple_weight_helper(['mins_played' => 0], ['all' => 0]);
    $this->simple_weight_helper(['mins_played' => 1], ['all' => 0.2]);
    $this->simple_weight_helper(['mins_played' => 14], ['all' => 0.2]);
    $this->simple_weight_helper(['mins_played' => 15], ['all' => 0.5]);
    $this->simple_weight_helper(['mins_played' => 30], ['all' => 0.5]);
    $this->simple_weight_helper(['mins_played' => 45], ['all' => 0.5]);
    $this->simple_weight_helper(['mins_played' => 46], ['all' => 0.8]);
    $this->simple_weight_helper(['mins_played' => 55], ['all' => 0.8]);
    $this->simple_weight_helper(['mins_played' => 89], ['all' => 0.8]);
    $this->simple_weight_helper(['mins_played' => 90], ['all' => 1,]);
  }

  public function test_own_goals(): void
  {
    $this->simple_weight_helper(['own_goals' => 1], ['a' => -6, 'm' => -6, 'd' => -6, 'g' => -6]);
  }

  public function test_yellow_card(): void
  {
    $this->simple_weight_helper(['yellow_card' => 1], ['a' => -1, 'm' => -1, 'd' => -2, 'g' => -3]);
  }

  public function test_red_card(): void
  {
    $this->simple_weight_helper(['red_card' => 1], ['a' => -3, 'm' => -3, 'd' => -5, 'g' => -6]);
  }

  public function test_sample_data(): void
  {
    $playerStatsGroup = new SplFileObject('./tests/Feature/fantasyPointSample.csv');
    $playerStatsGroup->setFlags(SplFileObject::READ_CSV);

    $colNames = [];
    foreach ($playerStatsGroup as $idx => $row) {
      if ($idx === 0) {
        foreach ($row as $col => $value) {
          $colNames[$col] = Str::snake($value);
        }
        continue;
      }
      if (count($row) === 1 && $row[0] === null) {
        break;
      }
      $playerStats = array_combine($colNames, $row);
      $calculator = new FantasyCalculator(FantasyCalculatorType::FANTASY_POINT, 0);
      // logger('POINT:' . $playerStats['p_o_i_n_t_s']);
      // logger('CAL_FANTASY_POINT:' . $calculator->getFantasyPoint($playerStats));
      $this->assertEquals($playerStats['p_o_i_n_t_s'], $calculator->calculate($playerStats));
    }
  }
}

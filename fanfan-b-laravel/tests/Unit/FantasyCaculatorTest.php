<?php

namespace Tests\Unit;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Libraries\Classes\FantasyCalculator;
use PHPUnit\Framework\TestCase;

class FantasyCaculatorTest extends TestCase
{
  /**
   * A basic test example.
   *
   * @return void
   */
  public function test_get_daily_position()
  {
    $mock1 = ['position' => PlayerDailyPosition::STRIKER, 'sub_position' => null];
    $mock2 = ['position' => PlayerDailyPosition::ATTACKING_MIDFIELDER, 'sub_position' => null];
    $mock3 = ['position' => PlayerDailyPosition::MIDFIELDER, 'sub_position' => null];
    $mock4 = ['position' => PlayerDailyPosition::DEFENSIVE_MIDFIELDER, 'sub_position' => null];
    $mock5 = ['position' => PlayerDailyPosition::DEFENDER, 'sub_position' => null];
    $mock6 = ['position' => PlayerDailyPosition::GOALKEEPER, 'sub_position' => null];
    $mock7 = ['position' => PlayerDailyPosition::SUBSTITUTE, 'sub_position' => PlayerPosition::ATTACKER];
    $mock8 = ['position' => PlayerDailyPosition::SUBSTITUTE, 'sub_position' => PlayerPosition::MIDFIELDER];
    $mock9 = ['position' => PlayerDailyPosition::SUBSTITUTE, 'sub_position' => PlayerPosition::DEFENDER];
    $mock10 = ['position' => PlayerDailyPosition::SUBSTITUTE, 'sub_position' => PlayerPosition::GOALKEEPER];

    $calculator = new FantasyCalculator(FantasyCalculatorType::FANTASY_POINT, 0);

    $this->assertEquals($calculator->getDailyPosition($mock1)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock1)['posValue'], PlayerDailyPosition::STRIKER);
    $this->assertEquals($calculator->getDailyPosition($mock2)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock2)['posValue'], PlayerDailyPosition::ATTACKING_MIDFIELDER);
    $this->assertEquals($calculator->getDailyPosition($mock3)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock3)['posValue'], PlayerDailyPosition::MIDFIELDER);
    $this->assertEquals($calculator->getDailyPosition($mock4)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock4)['posValue'], PlayerDailyPosition::DEFENSIVE_MIDFIELDER);
    $this->assertEquals($calculator->getDailyPosition($mock5)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock5)['posValue'], PlayerDailyPosition::DEFENDER);
    $this->assertEquals($calculator->getDailyPosition($mock6)['posType'], 'position');
    $this->assertEquals($calculator->getDailyPosition($mock6)['posValue'], PlayerDailyPosition::GOALKEEPER);
    $this->assertEquals($calculator->getDailyPosition($mock7)['posType'], 'sub_position');
    $this->assertEquals($calculator->getDailyPosition($mock7)['posValue'], PlayerPosition::ATTACKER);
    $this->assertEquals($calculator->getDailyPosition($mock8)['posType'], 'sub_position');
    $this->assertEquals($calculator->getDailyPosition($mock8)['posValue'], PlayerPosition::MIDFIELDER);
    $this->assertEquals($calculator->getDailyPosition($mock9)['posType'], 'sub_position');
    $this->assertEquals($calculator->getDailyPosition($mock9)['posValue'], PlayerPosition::DEFENDER);
    $this->assertEquals($calculator->getDailyPosition($mock10)['posType'], 'sub_position');
    $this->assertEquals($calculator->getDailyPosition($mock10)['posValue'], PlayerPosition::GOALKEEPER);
  }
}

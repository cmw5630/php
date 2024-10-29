<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
  /**
   * A basic test example.
   *
   * @return void
   */
  public function test_set_new_decimal()
  {
    $this->assertEquals(1, __setDecimal(1.4391, 0, 'round'));
    $this->assertEquals(1.4, __setDecimal(1.4391, 1, 'round'));
    $this->assertEquals(1.44, __setDecimal(1.4391, 2, 'round'));
    $this->assertEquals(1.439, __setDecimal(1.4391, 3, 'round'));
    $this->assertEquals(1.4391, __setDecimal(1.4391, 4, 'round'));

    // $this->assertEquals(2, __setDecimal2(1.4391, 0, 'ceil'));
    $this->assertEquals(1.5, __setDecimal(1.4391, 1, 'ceil'));
    $this->assertEquals(1.44, __setDecimal(1.4391, 2, 'ceil'));
    $this->assertEquals(1.44, __setDecimal(1.4391, 3, 'ceil'));
    $this->assertEquals(1.4391, __setDecimal(1.4391, 4, 'ceil'));

    $this->assertEquals(1, __setDecimal(1.4391, 0, 'floor'));
    $this->assertEquals(1.4, __setDecimal(1.4391, 1, 'floor'));
    $this->assertEquals(1.43, __setDecimal(1.4391, 2, 'floor'));
    $this->assertEquals(1.439, __setDecimal(1.4391, 3, 'floor'));
    $this->assertEquals(1.4391, __setDecimal(1.4391, 4, 'floor'));


    $this->assertEquals(-1, __setDecimal(-1.4391, 0, 'round'));
    $this->assertEquals(-1.4, __setDecimal(-1.4391, 1, 'round'));
    $this->assertEquals(-1.44, __setDecimal(-1.4391, 2, 'round'));
    $this->assertEquals(-1.439, __setDecimal(-1.4391, 3, 'round'));
    $this->assertEquals(-1.4391, __setDecimal(-1.4391, 4, 'round'));

    $this->assertEquals(-1, __setDecimal(-1.4391, 0, 'ceil'));
    $this->assertEquals(-1.4, __setDecimal(-1.4391, 1, 'ceil'));
    $this->assertEquals(-1.43, __setDecimal(-1.4391, 2, 'ceil'));
    $this->assertEquals(-1.439, __setDecimal(-1.4391, 3, 'ceil'));
    $this->assertEquals(-1.4391, __setDecimal(-1.4391, 4, 'ceil'));

    $this->assertEquals(-2, __setDecimal(-1.4391, 0, 'floor'));
    $this->assertEquals(-1.5, __setDecimal(-1.4391, 1, 'floor'));
    $this->assertEquals(-1.44, __setDecimal(-1.4391, 2, 'floor'));
    $this->assertEquals(-1.44, __setDecimal(-1.4391, 3, 'floor'));
    $this->assertEquals(-1.4392, __setDecimal(-1.4391, 4, 'floor'));

    $this->assertEquals(16.9, __setDecimal(16.9, 2, 'floor'));
    $this->assertEquals(16.9, __setDecimal(16.9, 2, 'ceil'));
    $this->assertEquals(16.9, __setDecimal(16.9, 2, 'round'));
    $this->assertEquals(16.84, __setDecimal(16.842, 2, 'floor'));
    $this->assertEquals(16.85, __setDecimal(16.842, 2, 'ceil'));
    $this->assertEquals(16.84, __setDecimal(16.842, 2, 'round'));
  }
}

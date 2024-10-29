<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlateCardTest extends TestCase
{
  // use RefreshDatabase;
  /**
   * A basic test example.
   *
   * @return void
   */

  protected function setUp(): void
  {
    parent::setUp();
    // if (env('DB_DATABASE') !== 'testing_soccer_api' || env('DB_DATA_DATABASE') !== 'testing_soccer_data') {
    //   dd('안전한 테스트를 위해 테이트 데이터베이스에 대한 제한을 걸어놨습니다. 현재 환경설정이 테스트 데이터베이스가 아닙니다. 조건을 봐주세요.');
    // }
    // $this->artisan('migrate:refresh');
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

  public function test_plate_card()
  {
    $this->assertTrue(true);
  }
}

<?php

use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('seasons', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->enum('server', array_keys(config('simulationpolicies')['server']))->default('asia')->comment('서버');
      $table->unsignedSmallInteger('week')->comment('주차');
      $table->timestamp('first_started_at')->comment('처음 경기 시작 시간');
      $table->timestamp('last_started_at')->comment('마지막 경기 시작 시간');
      $table->enum('active', YesNo::getValues())->default(YesNo::NO);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('seasons');
  }
};

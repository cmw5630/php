<?php

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('schedules', function (Blueprint $table) {
      $table->uuid('id')->primary()->comment('경기 id');
      $table->foreignUuid('league_id')->comment('리그 id')->constrained();
      $table->foreignUuid('season_id')->comment('시즌 id')->constrained();
      $table->char('home_formation_used', 5)->nullable()->comment('Home Team Formation Used');
      $table->char('away_formation_used', 5)->nullable()->comment('AWAY Team Formation Used');
      $table->foreignUuid('home_team_id')->comment('Home Team id')->constrained(Team::getModel()->getTable(), 'id');
      $table->foreignUuid('away_team_id')->comment('Away Team id')->constrained(Team::getModel()->getTable(), 'id');
      $table->unsignedSmallInteger('injury_one')->nullable();
      $table->unsignedSmallInteger('injury_two')->nullable();

      $table->unsignedTinyInteger('coverage_level')->comment('커버리지 레벨');
      $table->timestamp('started_at')->comment('경기 시작시간 UTC');
      $table->timestamp('ended_at')->nullable()->comment('경기 종료시간 UTC');
      // api 에서 갑자기 사라짐..(2022-11-11)
      // $table->timestamp('local_started_at')->comment('현지 기준 시작일');
      $table->unsignedTinyInteger('round')->nullable()->comment('라운드');
      $table->unsignedTinyInteger('ga_round')->nullable()->comment('가상 라운드');
      $table->enum('status', ScheduleStatus::getValues())->comment('상태');
      $table->enum('winner', ScheduleWinnerStatus::getValues())->nullable()->comment('home|away|draw');
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->disableForeignKeyConstraints();
    Schema::connection('data')->dropIfExists('schedules');
    Schema::connection('data')->enableForeignKeyConstraints();
  }
};

<?php

use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Models\simulation\SimulationApplicant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('schedules', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('league_id')->constrained();
      $table->foreignId('home_applicant_id')->constrained(SimulationApplicant::getModel()->getTable());
      $table->foreignId('away_applicant_id')->constrained(SimulationApplicant::getModel()->getTable());
      $table->unsignedInteger('round');
      $table->enum('winner', ScheduleWinnerStatus::getValues())->nullable()->comment('home,away,draw');
      $table->boolean('is_user_lineup_locked')->default(false)->index()->comment('라인업 복사 기준 flag/라인업 수정 가능 여부 유효성 검사');
      $table->boolean('is_next_lineup_ready')->default(false)->index()->comment('라인업 복사 완료 여부');
      $table->boolean('is_sim_ready')->default(false)->index()->comment('시뮬레이션 소켓 데이터셋 준비여부');
      $table->boolean('is_rank_completed')->default(false)->index()->comment('랭킹 계산 여부');
      $table->enum('status', SimulationScheduleStatus::getValues())->index()->comment('Fixture,Played,Playing');
      $table->unsignedSmallInteger('first_extra_minutes')->nullable();
      $table->unsignedSmallInteger('second_extra_minutes')->nullable();
      $table->timestamp('started_at')->comment('경기 예상 시작 시간');
      $table->timestamp('real_started_at')->nullable()->comment('경기(live) 실제 시작 시간');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('schedules');
  }
};

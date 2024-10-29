<?php

use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('game_possible_schedules', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('리그id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('season_id')->comment('시즌id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('schedule_id')->unique()->comment('스케쥴id')->constrained(Schedule::getModel()->getTable());
      $table->unsignedTinyInteger('round')->nullable()->comment('스케쥴의 라운드');
      $table->unsignedTinyInteger('ga_round')->nullable()->comment('스케쥴의 가상 라운드');
      $table->bigInteger('br_schedule_id')->nullable()->comment('매치트래커 스케쥴 id');
      $table->timestamp('ended_at')->nullable()->comment('경기 종료 시간');
      $table->enum('status', ScheduleStatus::getValues())->comment('상태');
      $table->boolean('wrapup_draft_completed')->default(false)->comment('라이브 종료 후 강화 성공');
      $table->boolean('wrapup_draft_cancelled')->default(false)->comment('라이브 종료 후 강화 취소');
      $table->boolean('wrapup_point_completed')->default(false)->comment('라이브 종료 후 인게임포인트 계산 성공');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('game_possible_schedules');
  }
};

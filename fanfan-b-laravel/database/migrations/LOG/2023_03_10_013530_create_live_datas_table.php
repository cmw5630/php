<?php

use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('live_logs', function (Blueprint $table) {
      $table->id();
      $table->smallInteger('collect_num')->comment('수집 번호');
      $table->foreignUuid('schedule_id')->comment('')->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('league_id')->comment('')->constrained(League::getModel()->getTable());
      $table->enum('status', ScheduleStatus::getValues())->comment('경기 상태');
      $table->jsonb('live_data')->comment('opta json response');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('live_logs');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

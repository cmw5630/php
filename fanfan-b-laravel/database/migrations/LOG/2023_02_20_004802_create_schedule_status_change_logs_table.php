<?php

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::connection('log')->create('schedule_status_change_logs', function (Blueprint $table) {
      $table->id();
      $table->smallInteger('index_changed')->default(0)->comment('바뀐 순번');
      $table->foreignUuid('schedule_id')->comment('스케쥴 id')->nullable()->constrained(Schedule::getModel()->getTable())->onDelete('SET NULL')->onUpdate('cascade');
      $table->enum('old_status', ScheduleStatus::getValues())->nullable()->comment('이전 상태');
      $table->enum('new_status', ScheduleStatus::getValues())->nullable()->comment('변경된 상태');
      $table->enum('old_winner', ScheduleWinnerStatus::getValues())->nullable()->comment('이전 winner');
      $table->enum('new_winner', ScheduleWinnerStatus::getValues())->nullable()->comment('변경된 winner');
      $table->timestamp('old_started_at')->nullable()->comment('이전 시작시간');
      $table->timestamp('new_started_at')->nullable()->comment('변경된 시작시간');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('schedule_status_change_logs');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

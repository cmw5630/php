<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\GamePossibleSchedule;

return new class extends Migration
{
  public function up()
  {
    Schema::create('game_schedules', function (Blueprint $table) {
      $table->id();
      $table->foreignId('game_id')->comment('게임 id')->constrained();
      $table->foreignUuid('schedule_id')->comment('스케쥴id')->constrained(GamePossibleSchedule::getModel()->getTable())->onDelete('cascade');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('game_schedules');
  }
};

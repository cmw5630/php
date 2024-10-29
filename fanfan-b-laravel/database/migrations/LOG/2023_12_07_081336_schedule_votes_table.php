<?php

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
    Schema::connection('log')->create('schedule_votes', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('schedule_id')->comment('스케쥴 id')->constrained(Schedule::getModel()->getTable());
      $table->unsignedBigInteger('home_vote')->default(1)->comment('홈팀 투표');
      $table->unsignedBigInteger('away_vote')->default(1)->comment('어웨이팀 투표');
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
    Schema::connection('log')->dropIfExists('schedule_votes');
  }
};

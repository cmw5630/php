<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::connection('data')->table('schedules', function (Blueprint $table) {
      $table->boolean('undecided')->default(false)->comment('경기 시간 미정');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('data')->table('schedules', function (Blueprint $table) {
      $table->dropColumn('undecided');
    });
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

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
      $table->unsignedTinyInteger('period_id')->after('round')->nullable()->comment('Period Id');
      $table->unsignedTinyInteger('match_length_min')->after('period_id')->default(0)->comment('경기 시간(분)');
      $table->unsignedTinyInteger('match_length_sec')->after('match_length_min')->default(0)->comment('경기 시간(초)');
      $table->unsignedTinyInteger('score_home')->after('match_length_sec')->default(0)->comment('HOME 점수');
      $table->unsignedTinyInteger('score_away')->after('score_home')->default(0)->comment('AWAY 점수');
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
      $table->dropColumn('period_id');
      $table->dropColumn('match_length_min');
      $table->dropColumn('match_length_sec');
      $table->dropColumn('score_home');
      $table->dropColumn('score_away');
    });
  }
};

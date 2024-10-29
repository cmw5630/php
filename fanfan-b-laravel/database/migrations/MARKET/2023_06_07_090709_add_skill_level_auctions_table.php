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
    Schema::table('auctions', function (Blueprint $table) {
      $table->smallInteger('draft_level')->nullable()->comment('성공한 강화 수치')->after('type');
      $table->smallInteger('attacking_level')->nullable()->comment('공격 레벨')->after('draft_level');
      $table->smallInteger('goalkeeping_level')->nullable()->comment('골키핑 레벨')->after('attacking_level');
      $table->smallInteger('passing_level')->nullable()->comment('패스 레벨')->after('goalkeeping_level');
      $table->smallInteger('defensive_level')->nullable()->comment('수비 레벨')->after('passing_level');
      $table->smallInteger('duel_level')->nullable()->comment('병합 레벨')->after('defensive_level');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('auctions', function (Blueprint $table) {
      $table->dropColumn([
        'draft_level',
        'attacking_level',
        'goalkeeping_level',
        'passing_level',
        'defensive_level',
        'duel_level',
      ]);
      //
    });
  }
};

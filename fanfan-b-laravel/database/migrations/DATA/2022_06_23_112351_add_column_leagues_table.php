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
    Schema::connection('data')->table('leagues', function (Blueprint $table) {
      $table->unsignedSmallInteger('order_no')->after('league_code')->nullable()->comment('스케쥴러 외 정렬 순서(custom)');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('data')->table('leagues', function (Blueprint $table) {
      $table->dropColumn('order_no');
    });
  }
};

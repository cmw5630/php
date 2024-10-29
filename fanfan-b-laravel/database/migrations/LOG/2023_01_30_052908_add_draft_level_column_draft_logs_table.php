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
    Schema::connection('log')->table('draft_logs', function (Blueprint $table) {
      $table->smallInteger('draft_level')->after('status')->nullable()->comment('성공한 강화 수치');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('log')->table('draft_logs', function (Blueprint $table) {
      $table->dropColumn('draft_level');
    });
  }
};

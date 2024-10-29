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
    Schema::table('users', function (Blueprint $table) {
      $table->integer('cash')->after('temp_password_expired_at')->default(0)->comment('사용자 총 cash');
      $table->integer('gold')->after('cash')->default(0)->comment('사용자 총 gold');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['cash', 'gold']);
    });
  }
};

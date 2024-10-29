<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('temp_password')->after('password')->nullable()->comment('임시 비밀번호');
      $table->timestamp('temp_password_expired_at')->after('temp_password')->nullable()->comment('임시 비밀번호 변경 만료일');
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
      $table->dropColumn('temp_password');
      $table->dropColumn('temp_password_expired_at');
    });
  }
};

<?php

use App\Enums\Nations;
use App\Enums\UserStatus;
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
      $table->enum(
        'status',
        UserStatus::getValues()
      )->default(UserStatus::NORMAL)->after('gold')->comment('상태');
      $table->string(
        'country',
        7
      )->default('unknown')->after('status')->comment('가입 국가');
      $table->boolean('name_change')->after('country')->default(false)->comment('닉네임 변경 여부');
      $table->enum('nation', Nations::getValues())->comment('가입시 선택한 국가')->after('country');
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
      $table->dropColumn(['status', 'country', 'name_change', 'nation']);
    });
  }
};

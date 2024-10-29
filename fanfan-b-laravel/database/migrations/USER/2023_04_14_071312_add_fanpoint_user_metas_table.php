<?php

use App\Enums\Nations;
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
    Schema::table('user_metas', function (Blueprint $table) {
      $table->integer('fan_point')->default(0)->comment('소각 재화 포인트')->after('photo_path');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('user_metas', function (Blueprint $table) {
      $table->dropColumn(['fan_point']);
    });
  }
};

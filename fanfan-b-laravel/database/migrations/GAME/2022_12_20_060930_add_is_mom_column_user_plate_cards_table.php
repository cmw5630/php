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
    Schema::table('user_plate_cards', function (Blueprint $table) {
      $table->boolean('is_mom')->after('draft_level')->default(false)->comment('MOM 여부');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('user_plate_cards', function (Blueprint $table) {
      $table->dropColumn('is_mom');
    });
  }
};

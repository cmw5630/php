<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('plate_cards', function (Blueprint $table) {
      $table->string('first_name_eng', 150)->after('first_name')->comment('영문화 이름')->index();
      $table->string('last_name_eng', 150)->after('last_name')->comment('영문화 이름')->index();
      $table->string('match_name', 150)->after('short_last_name')->comment('영문화 이름')->index();
      $table->string('match_name_eng', 150)->after('match_name')->index();
      $table->index('first_name');
      $table->index('last_name');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('plate_cards', function (Blueprint $table) {
      $table->dropColumn(['first_name_eng', 'last_name_eng', 'match_name', 'match_name_eng']);
    });
  }
};

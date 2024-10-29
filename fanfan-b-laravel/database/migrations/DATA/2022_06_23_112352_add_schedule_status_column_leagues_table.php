<?php

use App\Enums\Opta\League\LeagueStatusType;
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
    Schema::connection('data')->table('leagues', function (Blueprint $table) {
      $table->enum('schedule_status', LeagueStatusType::getValues())->after('status')->comment('스케쥴러용 상태');
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
      $table->dropColumn('schedule_status');
    });
  }
};

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
    Schema::connection('data')->table('schedules', function (Blueprint $table) {
      $table->string('stage_name')->after('season_id');
      $table->timestamp('stage_end_date')->after('season_id');
      $table->timestamp('stage_start_date')->after('season_id');
      $table->uuid('stage_format_id')->after('season_id');
      $table->uuid('stage_id')->after('season_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::connection('data')->table('schedules', function (Blueprint $table) {
      $table->dropColumn('stage_id');
      $table->dropColumn('stage_format_id');
      $table->dropColumn('stage_start_date');
      $table->dropColumn('stage_end_date');
      $table->dropColumn('stage_name');
    });
  }
};

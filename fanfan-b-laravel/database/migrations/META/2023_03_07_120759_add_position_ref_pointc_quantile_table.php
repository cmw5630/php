<?php

use App\Enums\Opta\Player\PlayerPosition;
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
    Schema::table('ref_pointc_quantiles', function (Blueprint $table) {
      $table->enum('position', PlayerPosition::getValues())->after('season_name_type')->comment('summary position');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('ref_pointc_quantiles', function (Blueprint $table) {
      $table->dropColumn('position');
    });
  }
};

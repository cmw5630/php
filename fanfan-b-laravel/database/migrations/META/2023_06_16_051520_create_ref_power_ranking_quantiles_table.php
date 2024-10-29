<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\meta\RefPriceGradeTransformMap;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_power_ranking_quantiles', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('기준 리그')->constrained(League::getModel()->getTable());
      $table->string('league_name')->comment('리그 이름');
      $table->foreignId('map_identification_id')->comment()->constrained(RefPriceGradeTransformMap::getModel()->getTable());
      $table->float('power_ranking')->nullable()->comment('cut point power ranking');
      $table->decimal('normalized_value', 20, 16)->nullable()->comment('normalized value');
      $table->float('mean')->nullable()->comment('(참조)이전 3시즌 power_ranking 평균');
      $table->float('stdev')->nullable()->comment('(참조)이전 3시즌 power_ranking 표준편차');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_power_ranking_quantiles');
  }
};

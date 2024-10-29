<?php

use App\Enums\Opta\Season\SeasonNameType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_pointc_quantiles', function (Blueprint $table) {
      $table->id();
      $table->char('playing_season_name', 9)->comment('point_c 계산 대상 시즌 name');
      $table->enum('season_name_type', SeasonNameType::getValues())->comment('point_c 계산 대상 시즌 nameType');
      $table->decimal('quantile_top', 5)->comment('기존 시즌 fantasy_point 상위 25%');
      $table->decimal('quantile_middle', 5)->comment('기존 시즌 fantasy_point 중앙값');
      $table->decimal('quantile_bottom', 5)->comment('기존 시즌 fantasy_point 하위 25%');
      $table->decimal('base_offset', 5)->comment('point_c + base_offset');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('ref_pointc_quantiles');
    Schema::enableForeignKeyConstraints();
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('ref_plate_c_quantiles', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('리그 id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('source_season_id')->comment('계산된 시즌 id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('price_init_season_id')->comment('가격 초기화가 적용될 시즌 id')->constrained(Season::getModel()->getTable());
      $table->decimal('quantile_ss', 5)->default(999)->comment('0%의 plate_c 값');
      $table->decimal('quantile_s', 5)->comment('2%의 plate_c 값');
      $table->decimal('quantile_a', 5)->comment('9% plate_c 값');
      $table->decimal('quantile_b', 5)->comment('23% plate_c 값');
      $table->decimal('quantile_c', 5)->comment('43% plate_c 값');
      $table->decimal('quantile_d', 5)->comment('70% plate_c 값');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('ref_plate_c_quantiles');
    Schema::enableForeignKeyConstraints();
  }
};

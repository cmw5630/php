<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_cardc_quantiles', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('leagud id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('playing_season_id')->comment('적용될 시즌 id')->constrained(Season::getModel()->getTable());
      $table->enum('summary_position', PlayerPosition::getValues());
      $table->decimal('quantile_ss', 5)->default(999)->comment('0%의  값');
      $table->decimal('quantile_s', 5)->comment('2%의 plate_c 값');
      $table->decimal('quantile_a', 5)->comment('9% plate_c 값');
      $table->decimal('quantile_b', 5)->comment('23% plate_c 값');
      $table->decimal('quantile_c', 5)->comment('43% plate_c 값');
      $table->decimal('quantile_d', 5)->comment('70% plate_c 값');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_cardc_quantiles');
  }
};

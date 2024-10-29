<?php

use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_avg_fps', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->comment('시즌 id')->constrained(Season::getModel()->getTable());
      // $table->smallInteger('round')->nullable()->comment('week');
      $table->enum('summary_position', PlayerPosition::getValues())->comment('플레이어 포지션');
      $table->float('rating_avg')->comment('rating 평균');
      $table->float('fantasy_point' . '_avg')->comment('판타지 포인트 평균');
      $table->float(FantasyPointCategoryType::GOALKEEPING . '_point_avg')->comment('goalkeeping 평균');
      $table->float(FantasyPointCategoryType::DUEL . '_point_avg')->comment('duel 평균');
      $table->float(FantasyPointCategoryType::PASSING . '_point_avg')->comment('passing 평균');
      $table->float(FantasyPointCategoryType::DEFENSIVE . '_point_avg')->comment('defensive 평균');
      $table->float(FantasyPointCategoryType::OFFENSIVE . '_point_avg')->comment('attacking 평균');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_avg_fps');
  }
};

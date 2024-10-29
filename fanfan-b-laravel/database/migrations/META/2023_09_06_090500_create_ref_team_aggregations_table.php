<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_team_aggregations', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
      $table->unsignedSmallInteger('win_count')->default(0)->comment('승리한 횟수');
      $table->unsignedSmallInteger('lose_count')->default(0)->comment('패배한 횟수');
      $table->unsignedSmallInteger('draw_count')->default(0)->comment('비긴 횟수');
      $table->json('recent_5_match')->nullable()->comment('최근 5경기 승무패');
      $table->json('rating_top3')->nullable()->comment('레이팅 상위 3명');
      $table->json('avg_fantasy_point_top1')->nullable()->comment('평균 판타지포인트 상위 1명');
      $table->float('avg_plus_goals')->comment('평균 득점');
      $table->float('avg_minus_goals')->comment('평균 실점');
      $table->unsignedSmallInteger('plus_goals')->comment('누적 득점');
      $table->unsignedSmallInteger('minus_goals')->comment('누적 실점');
      $table->smallInteger('goal_difference')->comment('골 득실점');
      $table->unsignedSmallInteger('match_count')->comment('누적 경기수');
      $table->unsignedInteger('win_point')->comment('승점');
      // $table->float('max_avg_plus_goals')->comment('Max 평균 득점');
      // $table->float('max_avg_minus_goals')->comment('Max 평균 실점');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_team_aggregations');
  }
};

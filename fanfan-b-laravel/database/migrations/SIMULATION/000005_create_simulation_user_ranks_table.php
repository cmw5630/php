<?php

use App\Enums\Simulation\SimulationRankStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('user_ranks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('applicant_id')->constrained();
      $table->foreignUuid('league_id')->constrained();
      $table->unsignedSmallInteger('ranking')->default(1);
      $table->unsignedSmallInteger('count_played')->default(0)->comment('게임 수');
      $table->unsignedSmallInteger('count_won')->default(0)->comment('승리 수');
      $table->unsignedSmallInteger('count_draw')->default(0)->comment('무승부 수');
      $table->unsignedSmallInteger('count_lost')->default(0)->comment('패배 수');
      $table->unsignedSmallInteger('points')->default(0)->comment('승점');
      $table->unsignedSmallInteger('goal')->default(0)->comment('득점');
      $table->unsignedSmallInteger('goal_against')->default(0)->comment('실점');
      $table->float('rating_avg')->default(0.00)->comment('평점 평균');
      $table->boolean('is_confirm')->default(false)->comment('confirm 여부');
      $table->enum('status', SimulationRankStatus::getValues())->comment('promotion,relegation,maintain');
      $table->timestamps();

      $table->unique([
        'applicant_id',
        'league_id',
      ]);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('user_ranks');
  }
};

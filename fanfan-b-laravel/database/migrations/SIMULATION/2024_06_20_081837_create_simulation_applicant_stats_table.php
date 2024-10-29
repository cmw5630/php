<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('applicant_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('league_id')->constrained();
      $table->foreignId('applicant_id')->constrained();
      $table->unsignedSmallInteger('count_played')->default(0)->comment('게임 수');
      $table->unsignedSmallInteger('count_won')->default(0)->comment('승리 수');
      $table->unsignedSmallInteger('count_draw')->default(0)->comment('무승부 수');
      $table->unsignedSmallInteger('count_lost')->default(0)->comment('패배 수');
      $table->unsignedSmallInteger('points')->default(0)->comment('승점');
      $table->float('rating_avg')->nullable();
      $table->float('overall_avg')->nullable();
      $table->unsignedSmallInteger('goal')->nullable();
      $table->float('goal_avg')->nullable();
      $table->unsignedSmallInteger('goal_against')->nullable();
      $table->float('goal_against_avg')->nullable();
      $table->jsonb('recent_5_match')->nullable();
      $table->float('scoring_first_won_avg')->nullable();
      $table->float('scoring_first_draw_avg')->nullable();
      $table->float('scoring_first_lost_avg')->nullable();
      $table->float('comeback_won_avg')->nullable();
      $table->float('comeback_lost_avg')->nullable();
      $table->jsonb('best_goal_players')->nullable()->comment('득점 1~3위 선수');
      $table->jsonb('best_assist_players')->nullable()->comment('어시스트 1~3위 선수');
      $table->jsonb('best_save_players')->nullable()->comment('세이브 1~3위 선수');
      $table->jsonb('best_rating_players')->nullable()->comment('평점 1~3위 선수');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('applicant_stats');
  }
};

<?php

use App\Enums\GameType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('games', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->comment('시즌id')->constrained(Season::getModel()->getTable());
      $table->tinyInteger('game_round_no')->comment('b2g게임라운드');
      $table->foreignId('user_id')->nullable()->comment('인플루언서의 user id')->constrained();
      $table->enum('mode', GameType::getValues())->default('normal')->comment('게임 모드');
      $table->integer('rewards')->comment('상금');
      $table->smallinteger('prize_rate')->comment('입상 비율');
      $table->boolean('is_popular')->default(false)->comment('메인 노출 여부');
      $table->timestamp('reservation_time')->nullable()->comment('예약 설정 시간');
      $table->string('banner_path', 100)->nullable()->comment('게임 배너 이미지');
      // 마지막 경기가 Played 상태가 아닌 다른 상태(Cancelled,Postponed,Suspended)로 인해 종료된 game에 대해 lockstatus 체크 완료 여부 체크 여부
      $table->tinyInteger('is_ingame_lock_released')->default(false)->comment('종료된 game에 대해 lockstatus 체크 완료 여부');
      $table->timestamp('start_date')->comment('첫 경기날짜');
      $table->timestamp('end_date')->comment('마지막 경기날짜');
      $table->timestamp('completed_at')->nullable()->comment('게임 완료 일시');
      $table->timestamp('rewarded_at')->nullable()->comment('게임 보상지급 완료 일시');

      $table->timestamps();
      // $table->unique(['season_id', 'game_round_no', 'user_id']);
      $table->unique(['season_id', 'game_round_no', 'mode']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('games');
  }
};

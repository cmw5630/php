<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('game_lineups', function (Blueprint $table) {
      $table->id();
      $table->foreignId('game_join_id')->comment('게임 참여 id')->constrained();
      $table->foreignUuid('schedule_id')->comment('해당 경기 id')->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('team_id')->comment('라인업 제출 시점 팀')->constrained(Team::getModel()->getTable());
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 id')->constrained();
      $table->foreignUuid('player_id')->comment('플레이어 id')->constrained();
      $table->enum('position', PlayerPosition::getValues());
      $table->decimal('m_fantasy_point', 8, 3)->default(0)->comment('판타지 포인트 가공점수');
      $table->smallInteger('place_index')->comment('카드위치 index 값');
      $table->integer('mins_played')->default(0)->comment('선수 mins_played');
      $table->boolean('is_team_changed')->default(false)->comment('라인업 제출 후 팀 변경 여부');
      $table->foreignUuid('changed_team_id')->nullable()->comment('이적 팀')->constrained(Team::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('game_lineups');
  }
};

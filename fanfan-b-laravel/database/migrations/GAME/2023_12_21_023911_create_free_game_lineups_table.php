<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerSubPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;
use App\Models\data\Team;
use App\Models\game\GameJoin;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_game_lineups', function (Blueprint $table) {
      $table->id();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->foreignId('player_id')->comment('플레이어 id')->constrained();
      $table->foreignUuid('schedule_id')->comment('schedule id')->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('team_id')->comment('라인업 제출 시점 팀')->constrained(Team::getModel()->getTable());
      $table->foreignId('game_join_id');

      $table->unsignedSmallInteger('formation_place')->comment('선수 formation place');
      $table->float('projection')->default(0);
      $table->jsonb('special_skills')->nullable()->comment('특수 스킬');
      $table->string('headshot_path')->nullable();

      $table->decimal('m_fantasy_point', 8, 3)->default(0);
      $table->float('level_weight');
      $table->float('rating');
      $table->boolean('is_mom');
      $table->smallInteger('draft_level')->nullable()->comment('성공한 강화 수치');
      $table->smallInteger('attacking_level')->nullable()->comment('공격 레벨');
      $table->smallInteger('goalkeeping_level')->nullable()->comment('골키핑 레벨');
      $table->smallInteger('passing_level')->nullable()->comment('패스 레벨');
      $table->smallInteger('defensive_level')->nullable()->comment('수비 레벨');
      $table->smallInteger('duel_level')->nullable()->comment('병합 레벨');
      $table->enum('card_grade', CardGrade::getValues())->index()->default(CardGrade::NONE)->comment('카드 등급');
      $table->enum('position', PlayerPosition::getValues())->comment('경기 포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      // 최종 오버롤(포지션 별 계수가 다름)
      $table->enum('sub_position', PlayerSubPosition::getValues())->comment('세부 포지션');
      $table->enum('second_position', PlayerSubPosition::getValues())->nullable()->comment('세컨드 포지션');
      $table->enum('third_position', PlayerSubPosition::getValues())->nullable()->comment('써드 포지션');
      $table->jsonb('final_overall')->comment('포지션 별 최종 오버롤');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_game_lineups');
  }
};

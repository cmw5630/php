<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_card_shuffle_memories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->foreignId('free_card_meta_id')->comment('셔플 카드 id')->constrained()->onDelete('cascade');
      $table->foreignUuid('schedule_id')->comment('schedule id')->constrained(Schedule::getModel()->getTable());
      $table->boolean('is_open')->comment('카드 뒤집기 여부');
      $table->smallInteger('mp')->comment('appearances');
      $table->smallInteger('goals')->comment('goals');
      $table->smallInteger('assists')->comment('assists');
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('draft_schedule_id')->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
      $table->float('projection');
      $table->unsignedSmallInteger('formation_place')->nullable()->comment('선수 formation place');
      $table->jsonb('special_skills')->nullable()->comment('특수 스킬');
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
      $table->string('headshot_path');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_card_shuffle_memories');
  }
};

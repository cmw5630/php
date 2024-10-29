<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_card_stacks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->float('level_weight');
      $table->smallInteger('draft_level')->nullable()->comment('성공한 강화 수치');
      $table->smallInteger('attacking_level')->nullable()->comment('공격 레벨');
      $table->smallInteger('goalkeeping_level')->nullable()->comment('골키핑 레벨');
      $table->smallInteger('passing_level')->nullable()->comment('패스 레벨');
      $table->smallInteger('defensive_level')->nullable()->comment('수비 레벨');
      $table->smallInteger('duel_level')->nullable()->comment('병합 레벨');
      $table->enum('card_grade', CardGrade::getValues())->index()->default(CardGrade::NONE)->comment('카드 등급');
      $table->enum('position', PlayerPosition::getValues())->comment('경기 포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_card_stacks');
  }
};

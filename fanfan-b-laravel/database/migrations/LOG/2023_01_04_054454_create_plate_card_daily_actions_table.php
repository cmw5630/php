<?php

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\PlateCardActionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('plate_card_daily_actions', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable())->comment('선수 id');
      $table->foreignUuid('season_id')->constrained()->comment('시즌 id');
      $table->enum('position', PlayerPosition::getValues())->comment('선수 포지션');
      $table->unsignedInteger(PlateCardActionType::STATS.'_count')->default(0)->comment('상세 페이지 접근 수');
      $table->unsignedInteger(PlateCardActionType::PLATE_ORDER.'_count')->default(0)->comment('플레이트카드 검색 수');
      $table->unsignedInteger(PlateCardActionType::UPGRADE .'_count')->default(0)->comment('선수 강화 수');
      $table->unsignedInteger(PlateCardActionType::LINEUP.'_count')->default(0)->comment('라인업 포함 수');
      $table->date('based_at')->index()->comment('표출 기준 날짜');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('plate_card_daily_actions');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

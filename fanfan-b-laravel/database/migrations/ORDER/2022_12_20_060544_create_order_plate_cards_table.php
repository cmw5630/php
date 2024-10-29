<?php

use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\PointType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('order_plate_cards', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->comment('주문 id')->constrained();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->foreignUuid('league_id')->comment('리그 id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('team_id')->comment('팀 id')->constrained(Team::getModel()->getTable());
      $table->enum('grade', OriginGrade::getValues())->comment('카드 등급');
      $table->enum('position', PlayerPosition::getValues())->comment('포지션');
      $table->unsignedInteger('price')->comment('플레이트카드 가격');
      $table->enum('point_type', PointType::getValues())->comment('포인트 타입');
      $table->unsignedSmallInteger('quantity')->max(20)->comment('수량');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('order_plate_cards');
  }
};

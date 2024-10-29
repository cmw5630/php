<?php

use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_plate_card_ranks', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->comment('시즌 id')->constrained();
      $table->foreignUuid('player_id')->comment('플레이어 id')->constrained();
      $table->enum('position', PlayerPosition::getValues())->comment('경기 포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      $table->unsignedInteger('overall')->comment('오버롤');
      $table->enum('grade', OriginGrade::getValues())->nullable()->comment('카드 가격 등급');
      $table->float('fantasy_point')->default(0);
      $table->string('match_name', 100)->comment('경기 상 선수이름');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_plate_card_ranks');
  }
};

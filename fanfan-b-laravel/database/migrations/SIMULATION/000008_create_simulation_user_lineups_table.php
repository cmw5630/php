<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Libraries\Classes\User;
use App\Models\game\Player;
use App\Models\user\UserPlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('user_lineups', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_lineup_meta_id')->constrained();
      $table->foreignId('user_plate_card_id')->unique()->comment('사용자 카드 id')->constrained(UserPlateCard::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('player id')->constrained(Player::getModel()->getTable());
      $table->unsignedSmallInteger('formation_place')->nullable();
      $table->boolean('game_started')->default(false)->comment('선발선수 여부');
      $table->enum('position', PlayerPosition::getAllPositions());
      $table->enum('sub_position', PlayerSubPosition::getValues());

      $table->timestamps();
      $table->unique([
        'user_lineup_meta_id',
        'player_id'
      ]);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('user_lineups');
  }
};

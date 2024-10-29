<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;
use App\Models\user\UserPlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('lineups', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lineup_meta_id')->constrained();
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 id')->constrained(UserPlateCard::getModel()->getTable(), 'id');
      $table->foreignUuid('player_id')->comment('player id')->constrained(Player::getModel()->getTable());
      $table->unsignedSmallInteger('formation_place')->nullable();
      $table->boolean('game_started')->default(false)->comment('선발선수 여부');
      $table->enum('position', PlayerPosition::getAllPositions());
      $table->enum('sub_position', PlayerSubPosition::getValues());

      $table->unsignedInteger('stamina')->nullable();
      $table->float('rating')->nullable()->comment('평점');
      $table->boolean('is_mom')->default(false)->comment('mom 여부');
      $table->unsignedSmallInteger('goal')->default(0);
      $table->unsignedSmallInteger('assist')->default(0);
      $table->unsignedSmallInteger('save')->default(0);
      $table->unsignedSmallInteger('key_pass')->default(0);
      $table->boolean('is_changed')->default(false)->comment('in, out 교체 대상이 되는  선수');
      $table->unsignedSmallInteger('yellow_card_count')->default(0);
      $table->unsignedSmallInteger('red_card_count')->default(0);

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('lineups');
  }
};

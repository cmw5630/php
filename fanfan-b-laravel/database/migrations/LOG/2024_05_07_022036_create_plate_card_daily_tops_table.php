<?php

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\PlateCardActionType;
use App\Models\game\Player;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->
    create('plate_card_daily_tops', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable())->comment('선수 id');
      $table->foreignUuid('season_id')->constrained()->comment('시즌 id');
      $table->enum('position', PlayerPosition::getValues())->comment('선수 포지션');
      $table->smallInteger('matches')->default(0)->comment('경기 수');
      $table->smallInteger('goals')->default(0)->comment('골 수');
      $table->smallInteger('assists')->default(0)->comment('어시스트 수');
      $table->smallInteger('clean_sheets')->default(0)->comment('클린시트 수');
      $table->smallInteger('saves')->default(0)->comment('세이브 수');
      $table->date('based_at')->index()->comment('표출 기준 날짜');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('plate_card_daily_tops');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

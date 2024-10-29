<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('event_cards', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('schedule_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->unsignedSmallInteger('slot')->comment('index');
      $table->enum('type', ['YC', 'Y2C', 'RC'])->comment('YC (yellow) | Y2C (second yellow) | RC (red)');
      $table->unsignedSmallInteger('period_id');
      $table->unsignedSmallInteger('time_min')->comment('교체 시간(분)');
      $table->string('time_min_sec', 6)->comment('교체 시간(분,초)');
      $table->foreignUuid('player_id')->nullable()->constrained(Player::getModel()->getTable());
      $table->unsignedBigInteger('opta_event_id');
      $table->string('card_reason');
      $table->timestamp('timestamp');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('event_cards');
  }
};

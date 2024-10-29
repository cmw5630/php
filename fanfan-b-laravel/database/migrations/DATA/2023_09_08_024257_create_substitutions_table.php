<?php

use App\Enums\Opta\Player\SubstituteReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('substitutions', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('schedule_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->unsignedSmallInteger('slot')->comment('index');
      $table->unsignedSmallInteger('period_id');
      $table->unsignedSmallInteger('time_min')->comment('교체 시간(분)');
      $table->string('time_min_sec', 6)->comment('교체 시간(분,초)');
      $table->foreignUuid('player_on_id')->comment('교체 in')->constrained(Player::getModel()->getTable());
      $table->foreignUuid('player_off_id')->comment('교체 out')->constrained(Player::getModel()->getTable());
      $table->enum('sub_reason', SubstituteReason::getValues())->comment('교체 시간');
      $table->timestamp('timestamp');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('substitutions');
  }
};

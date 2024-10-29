<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('event_goals', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('schedule_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->unsignedSmallInteger('slot')->comment('index');
      $table->enum('type', ['G', 'OG', 'PG'])->comment('G (goal) | OG (own goal) | PG (penalty goal)');
      $table->unsignedSmallInteger('period_id');
      $table->unsignedSmallInteger('time_min')->comment('교체 시간(분)');
      $table->string('time_min_sec', 6)->comment('교체 시간(분,초)');
      $table->foreignUuid('scorer_id')->constrained(Player::getModel()->getTable());
      $table->string('scorer_name');
      $table->foreignUuid('assist_player_id')->nullable()->constrained(Player::getModel()->getTable());
      $table->string('assist_player_name')->nullable();
      $table->unsignedBigInteger('opta_event_id');
      $table->unsignedSmallInteger('home_score');
      $table->unsignedSmallInteger('away_score');
      $table->timestamp('timestamp');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('event_goals');
  }
};

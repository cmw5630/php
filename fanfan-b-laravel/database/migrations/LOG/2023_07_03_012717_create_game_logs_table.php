<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Game;
use Illuminate\Database\Query\Expression;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('game_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_id')->nullable()->comment('관리자 ID')->constrained(new Expression(config('database.connections.admin.database') . '.admins'));
      $table->foreignId('game_id')->comment('게임 ID')->constrained(Game::getModel()->getTable());
      $table->timestamp('canceled_at')->comment('취소 날짜');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('game_logs');
  }
};

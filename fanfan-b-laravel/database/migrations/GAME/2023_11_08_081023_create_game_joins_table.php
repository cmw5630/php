<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('game_joins', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->string('name')->comment('사용자 닉네임');
      $table->foreignId('game_id')->comment('게임 id')->constrained();
      $table->smallInteger('ranking')->nullable()->comment('순위');
      $table->float('point')->default(0)->comment('획득 점수');
      $table->bigInteger('reward')->default(0)->comment('획득 상금');
      $table->enum('formation', config('constant.LINEUP_FORMATION'))->default('442')->comment('제출한 라인업의 포메이션');
      $table->timestamps();

      $table->unique(['user_id', 'game_id']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('game_joins');
  }
};

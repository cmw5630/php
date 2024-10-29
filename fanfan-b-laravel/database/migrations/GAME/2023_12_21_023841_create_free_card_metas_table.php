<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_card_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained();
      $table->foreignId('game_id')->constrained();
      $table->smallInteger('shuffle_count')->default(0);
      $table->timestamp('adv_started_at')->nullable();
      $table->timestamps();
      $table->unique(['user_id', 'game_id']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_card_metas');
  }
};

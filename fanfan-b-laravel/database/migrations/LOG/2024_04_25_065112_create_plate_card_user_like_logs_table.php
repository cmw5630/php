<?php

use App\Models\game\PlateCard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('plate_card_user_like_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained(User::getModel()->getTable());
      $table->foreignId('plate_card_id')->comment('플레이트 카드 id')->constrained(PlateCard::getModel()->getTable());
      $table->timestamps();
      $table->unique(['user_id', 'plate_card_id']);
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('plate_card_user_like_logs');
  }
};

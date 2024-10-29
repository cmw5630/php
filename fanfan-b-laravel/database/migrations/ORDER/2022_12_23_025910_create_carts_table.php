<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('carts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->unsignedInteger('price')->comment('플레이트카드 가격');
      $table->unsignedSmallInteger('quantity')->max(20)->comment('수량');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('carts');
  }
};

<?php

use App\Enums\Opta\Card\CardGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_market_minimum_prices', function (Blueprint $table) {
      $table->id();
      $table->enum('card_grade', CardGrade::getValues())->comment('카드 등급');
      $table->unsignedInteger('draft_level')->comment('특수 스탯');
      $table->enum('draft_type', ['single', 'combined'])->comment('강화 타입');
      $table->unsignedInteger('min_gold')->comment('최소 골드');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_market_minimum_prices');
  }
};

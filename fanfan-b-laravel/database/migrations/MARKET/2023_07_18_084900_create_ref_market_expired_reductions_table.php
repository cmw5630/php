<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_market_expire_reductions', function (Blueprint $table) {
      $table->id();
      $table->unsignedSmallInteger('expired_count')->comment('경매 유찰 횟수');
      $table->double('reduction_rate', 3, 2)->comment('저감율');
      $table->json('period_options')->comment('경매 시간 옶션값');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_market_expire_reductions');
  }
};

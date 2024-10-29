<?php

use App\Enums\AuctionBidStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('auction_bids', function (Blueprint $table) {
      $table->id();
      $table->foreignId('auction_id')->comment('경매 ID')->constrained();
      $table->foreignId('user_id')->constrained();
      $table->unsignedBigInteger('price')->comment('경매 참여 금액');
      $table->enum('status', AuctionBidStatus::getValues())->default('failed')->comment('경매 참여 상태');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('auction_bids');
  }
};

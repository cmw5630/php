<?php

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Auction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('auctions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained();
      $table->foreignId('parent_auction_id')->nullable()->comment('유찰 재등록 경매일 경우 부모 id')->constrained(Auction::getModel()->getTable());
      $table->foreignId('user_plate_card_id')->comment('경매 대상 카드')->constrained();
      $table->enum('type', AuctionType::getValues())->default(AuctionType::OPEN)->index()->comment('경매 타입');
      $table->enum('status', AuctionStatus::getValues())->default(AuctionStatus::BIDDING)->index()->comment('경매 상태');
      $table->unsignedBigInteger('start_price')->comment('경매 시작 가격');
      $table->unsignedBigInteger('buynow_price')->comment('즉시 구매 가격');
      $table->unsignedSmallInteger('expired_count')->default(0)->comment('경매 유찰 횟수');
      $table->unsignedSmallInteger('period')->comment('경매 진행 시간');
      $table->timestamp('expired_at')->comment('경매 종료 시간');
      $table->timestamp('canceled_at')->nullable()->comment('경매 취소 시간');
      $table->timestamp('sold_at')->nullable()->comment('판매 완료 시간(블라인드)');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('auctions');
  }
};

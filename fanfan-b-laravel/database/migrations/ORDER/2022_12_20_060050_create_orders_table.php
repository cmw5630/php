<?php

use App\Enums\PointType;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->unsignedInteger('total_price')->comment('총 가격');
      $table->enum('point_type', PointType::getValues())->comment('포인트 타입');
      $table->enum('order_status', PurchaseOrderStatus::getValues())->default(PurchaseOrderStatus::COMPLETE)->comment('주문 상태');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('orders');
  }
};

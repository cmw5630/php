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
    Schema::create('draft_orders', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained();
      $table->foreignId('user_plate_card_id')->constrained();
      $table->integer('upgrade_point')->comment('강화 포인트');
      $table->enum('upgrade_point_type', PointType::getValues())->comment('강화 포인트 타입');
      $table->enum('order_status', PurchaseOrderStatus::getValues())->default(PurchaseOrderStatus::COMPLETE)->comment('주문 상태');
      $table->timestamps();
      $table->unique('user_plate_card_id');
    });
  }

  public function down()
  {
    Schema::dropIfExists('draft_orders');
  }
};

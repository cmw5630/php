<?php

use App\Enums\PointRefType;
use App\Enums\PointType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('user_point_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 ID')->constrained(User::getModel()->getTable());
      $table->enum('point_type', PointType::getValues())->comment('포인트 타입');
      $table->enum('point_ref_type', PointRefType::getValues())->comment('포인트 사용 타입');
      $table->bigInteger('amount')->comment('증감 금액');
      // reference ID 심을건지
      // $table->foreignId('order_id')->nullable()->comment('주문 ID')->constrained();
      $table->string('description')->comment('상세 내용');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('user_point_logs');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

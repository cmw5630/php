<?php

use App\Enums\RedeemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('admin')->create('redeems', function (Blueprint $table) {
      $table->id();
      $table->string('redeem_code')->unique()->comment('redeem 코드');
      $table->string('redeem_name');
      $table->timestamp('requested_at')->comment('redeem 시작일');
      $table->timestamp('completed_at')->nullable()->comment('redeem 종료일');
      $table->enum('status', RedeemStatus::getValues())->default('active')->comment('상태');
      $table->json('reward')->comment('보상');
      $table->text('description')->nullable()->comment('redeem 설명');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('redeems');
  }
};

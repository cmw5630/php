<?php

use App\Enums\RedeemStatus;
use App\Libraries\Classes\Blueprint;
use App\Models\admin\Redeem;
use App\Models\user\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('user_redeems', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained(User::getModel()->getTable());
      $table->foreignId('redeem_id')->comment('리딤 아이디')->constrained(Redeem::getModel()->getTable());
      $table->enum('status',RedeemStatus::getValues())->default('active')->comment('상태');
      $table->timestamp('used_at')->nullable()->comment('사용일자');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('user_redeems');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;
use App\Models\user\UserReferral;

return new class extends Migration
{
  public function up()
  {
    Schema::create('user_referrals', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->comment('사용자 id')->constrained(User::getModel()->getTable());
      $table->foreignId('referral_id')->index()->nullable()->comment('추천코드 id')->constrained(UserReferral::getModel()->getTable());
      $table->string('user_referral_code', 8)->comment('사용자의 추천코드');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('user_referrals');
  }
};

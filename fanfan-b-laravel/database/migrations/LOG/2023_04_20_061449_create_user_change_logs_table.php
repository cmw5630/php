<?php

use App\Enums\UserChangeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;
use App\Models\user\UserReferral;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('user_change_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained(User::getModel()->getTable());
      $table->unsignedBigInteger('old_referral_id')->nullable()->comment('이전 추천코드 id');
      $table->foreignId('new_referral_id')->nullable()->comment('새로운 추천코드 id')->constrained(UserReferral::getModel()->getTable());
      $table->timestamp('join_at')->nullable()->comment('탈퇴시 남길 가입일시');
      $table->enum('change_type', UserChangeType::getValues());
      $table->string('description')->comment('상세 내용');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('user_change_logs');
  }
};

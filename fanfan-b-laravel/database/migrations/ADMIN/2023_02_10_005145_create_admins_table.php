<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('admin')->create('admins', function (Blueprint $table) {
      $table->id();
      $table->string('login_id', 100)->comment('로그인 아이디');
      $table->string('password')->nullable();
      $table->string('nickname')->nullable()->comment('닉네임');
      $table->string('name')->nullable()->comment('사용자 명');
      $table->text('access_token')->nullable();
      $table->string('remember_token')->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('admin')->dropIfExists('admins');
  }
};

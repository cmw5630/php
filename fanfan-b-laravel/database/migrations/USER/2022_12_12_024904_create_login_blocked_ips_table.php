<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('login_blocked_ips', function (Blueprint $table) {
      $table->id();
      $table->ipAddress();
      $table->unsignedTinyInteger('count')->comment('차단 횟수');
      $table->timestamp('until')->comment('차단 종료 일시');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('login_blocked_ips');
  }
};

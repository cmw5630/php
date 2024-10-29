<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('user_withdraw_logs', function (Blueprint $table) {
      $table->id();
      $table->string('reason', 5)->comment('사유 코드 W01');
      $table->unsignedInteger('count')->default(0)->comment('사유 별 count');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('user_withdraw_logs');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('admin')->create('blocked_ips', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_id')->constrained();
      $table->ipAddress();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('admin')->dropIfExists('blocked_ips');
  }
};

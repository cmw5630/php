<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('user_login_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained(User::getModel()->getTable());
      $table->ipAddress();
      $table->string('agent');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('user_login_logs');
  }
};

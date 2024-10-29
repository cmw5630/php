<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('linked_social_accounts', function (Blueprint $table) {
      $table->id();
      $table->string('provider_id');
      $table->string('provider_name');
      $table->foreignId('user_id')->constrained();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('linked_social_accounts');
  }
};

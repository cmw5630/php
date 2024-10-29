<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('influencers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->string('name');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('influencers');
  }
};

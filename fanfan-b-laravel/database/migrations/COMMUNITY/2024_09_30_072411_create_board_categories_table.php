<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('board_categories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('board_id')->constrained();
      $table->string('name', 100)->comment('카테고리 이름');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('board_categories');
  }
};

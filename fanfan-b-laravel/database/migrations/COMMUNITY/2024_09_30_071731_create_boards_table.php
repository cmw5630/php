<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('boards', function (Blueprint $table) {
      $table->id();
      $table->string('name', 50)->comment('게시판 이름');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('boards');
  }
};

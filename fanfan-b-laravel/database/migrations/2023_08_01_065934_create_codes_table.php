<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('codes', function (Blueprint $table) {
      $table->id();
      $table->string('category', 5)->comment('카테고리');
      $table->string('code', 5)->nullable()->comment('코드');
      $table->string('name', 255)->comment('이름');
      $table->string('description', 255)->nullable()->comment('설명');
      $table->unsignedSmallInteger('order_no')->nullable()->comment('순서');
      $table->timestamps();

      $table->unique(['category', 'code']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('codes');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->disableForeignKeyConstraints();
    Schema::connection('simulation')->create('tiers', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->unsignedSmallInteger('level')->comment('레벨');
      $table->string('name')->comment('티어 이름');
      $table->timestamps();
    });
    Schema::connection('simulation')->enableForeignKeyConstraints();
  }

  public function down()
  {
    Schema::connection('simulation')->disableForeignKeyConstraints();
    Schema::connection('simulation')->dropIfExists('tiers');
    Schema::connection('simulation')->enableForeignKeyConstraints();
  }
};

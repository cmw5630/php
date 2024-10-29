<?php

use App\Enums\Opta\Card\OriginGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_price_grade_transform_maps', function (Blueprint $table) {
      $table->id();
      $table->string(OriginGrade::SS)->nullable()->comment();
      $table->string(OriginGrade::S)->nullable()->comment();
      $table->string(OriginGrade::A)->nullable()->comment();
      $table->string(OriginGrade::B)->nullable()->comment();
      $table->string(OriginGrade::C)->nullable()->comment();
      $table->string(OriginGrade::D)->nullable()->comment();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_price_grade_transform_maps');
  }
};

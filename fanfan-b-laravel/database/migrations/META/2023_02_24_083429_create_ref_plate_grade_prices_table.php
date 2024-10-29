<?php

use App\Enums\Opta\Card\OriginGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_plate_grade_prices', function (Blueprint $table) {
      $table->id();
      $table->float('percentile_point');
      $table->enum('grade', OriginGrade::getValues())->comment('카드 grade');
      $table->integer('price')->comment('카드의 가격 임시');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_plate_grade_prices');
  }
};

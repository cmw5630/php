<?php

use App\Enums\Opta\Card\OriginGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_draft_prices', function (Blueprint $table) {
      $table->id();
      $table->integer('level')->comment('level');
      $table->integer(OriginGrade::SS)->comment('SS');
      $table->integer(OriginGrade::S)->comment('S');
      $table->integer(OriginGrade::A)->comment('A');
      $table->integer(OriginGrade::B)->comment('B');
      $table->integer(OriginGrade::C)->comment('C');
      $table->integer(OriginGrade::D)->comment('D');
      $table->float('rate')->nullable()->comment('비율');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_draft_prices');
  }
};

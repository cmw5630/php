<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\OriginGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('ref_burn_cards', function (Blueprint $table) {
      $table->id();
      $table->enum('price_grade', OriginGrade::getValues());
      $table->string('level_range', 3)->comment('범위');
      foreach (array_reverse(CardGrade::getGrades()) as $grade) {
        $table->unsignedMediumInteger($grade.'_min');
        $table->unsignedMediumInteger($grade.'_max');
      }
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('ref_burn_cards');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('free_field_level_grade_pools', function (Blueprint $table) {
      $table->id();
      $table->string('lv_grade')->comment('level & grade');
      $table->decimal('rate', 10, 9)->nullable()->comment('누적 확률');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('free_field_level_grade_pools');
  }
};

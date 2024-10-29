<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_country_codes', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->char('alpha_2_code', 2)->nullable();
      $table->char('alpha_3_code', 3);
      $table->char('numeric', 3)->nullable();
      $table->uuid('nationality_id')->nullable()->comment('opta country id');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_country_codes');
  }
};

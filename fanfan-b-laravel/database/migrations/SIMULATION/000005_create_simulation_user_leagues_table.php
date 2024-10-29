<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('user_leagues', function (Blueprint $table) {
      $table->id();
      $table->foreignId('applicant_id')->constrained();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('league_id')->nullable()->constrained();
      $table->foreignUuid('division_id')->constrained();
      $table->timestamps();

      $table->unique([
        'applicant_id',
        'season_id'
      ]);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('user_leagues');
  }
};

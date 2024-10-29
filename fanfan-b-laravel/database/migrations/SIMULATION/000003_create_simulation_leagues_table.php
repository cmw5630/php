<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('leagues', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('division_id')->constrained();
      $table->unsignedSmallInteger('league_no')->default(1);
      // $table->boolean('is_fixed')->comment('고정된 리그 여부');
      $table->timestamps();

      $table->unique([
        'season_id',
        'division_id',
        'league_no'
      ]);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('leagues');
  }
};

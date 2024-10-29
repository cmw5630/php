<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('divisions', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('tier_id')->constrained();
      $table->integer('division_no');
      $table->unsignedSmallInteger('max_league_count')->nullable()->comment('최대 리그 수');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('divisions');
  }
};

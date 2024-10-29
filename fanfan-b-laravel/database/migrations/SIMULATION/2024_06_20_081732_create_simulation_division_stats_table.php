<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('division_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('division_id')->constrained();
      $table->foreignUuid('season_id')->constrained();
      $table->float('overall_avg')->nullable()->comment('그룹의 이번 시즌 평균 오버롤');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('division_stats');
  }
};

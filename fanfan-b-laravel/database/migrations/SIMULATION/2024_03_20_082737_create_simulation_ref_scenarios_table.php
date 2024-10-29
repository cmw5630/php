<?php

use App\Enums\Simulation\ScheduleWinnerStatus;
use App\Enums\Simulation\SimulationTeamSide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('ref_scenarios', function (Blueprint $table) {
      $table->id();
      $table->unsignedSmallInteger('home_score');
      $table->unsignedSmallInteger('away_score');
      $table->enum('first_goal', SimulationTeamSide::getValues())->nullable();
      $table->enum('winner', ScheduleWinnerStatus::getValues());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('ref_scenarios');
  }
};

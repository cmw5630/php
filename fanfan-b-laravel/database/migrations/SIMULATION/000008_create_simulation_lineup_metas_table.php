<?php

use App\Enums\Simulation\SimulationTeamSide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Libraries\Classes\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('lineup_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('applicant_id')->constrained();
      $table->foreignUuid('schedule_id')->constrained();
      $table->enum('formation_used', config('constant.LINEUP_FORMATION'))->default('442')->comment('Team Formation Used');
      $table->enum('team_side', SimulationTeamSide::getValues());
      $table->string('goal_winner_comb', 10)->nullable()->comment('선제골_위너');
      $table->float('attack_power')->comment('공격력');
      $table->float('defence_power')->comment('수비력');
      $table->float('expected_score')->comment('기대득점');
      $table->unsignedSmallInteger('substitution_count');
      $table->unsignedSmallInteger('playing_style')->comment('공격 성향')->default(4);
      $table->unsignedSmallInteger('defensive_line')->comment('수비 라인')->default(2);
      $table->unsignedSmallInteger('score')->nullable();
      $table->decimal('rating', 3, 1)->nullable();
      $table->boolean('is_result_checked')->default(false);
      $table->timestamps();

      $table->unique(['schedule_id', 'applicant_id']);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('lineup_metas');
  }
};

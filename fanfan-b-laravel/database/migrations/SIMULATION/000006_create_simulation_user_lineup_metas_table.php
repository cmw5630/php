<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('user_lineup_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('applicant_id')->constrained();
      $table->enum('formation_used', config('constant.LINEUP_FORMATION'))->default('442')->nullable()->comment('Team Formation Used');
      $table->unsignedSmallInteger('substitution_count')->default(1);
      $table->unsignedSmallInteger('playing_style')->comment('공격 성향')->default(4);
      $table->unsignedSmallInteger('defensive_line')->comment('수비 라인')->default(2);
      $table->float('attack_power')->comment('공격력');
      $table->float('defence_power')->comment('수비력');
      $table->float('expected_score')->comment('기대득점');
      $table->boolean('is_first')->default(true)->comment('라인업 수정 여부');
      $table->boolean('is_in_trouble')->default(false)->comment('라인업 문제 여부');
      $table->timestamps();

      $table->unique(['applicant_id']);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('user_lineup_metas');
  }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\simulation\RefSimulationScenario;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('ref_sequences', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ref_scenario_id')->constrained(RefSimulationScenario::getModel()->getTable());
      $table->unsignedSmallInteger('seq')->comment('시퀀스 번호');
      $table->unsignedSmallInteger('playing_seconds')->nullable()->comment('경기시간(초)');
      $table->enum('nth_half', ['first', 'second'])->comment('전/후반');
      $table->enum('attack_direction', ['home', 'away'])->nullable()->comment('공격 방향');
      $table->unsignedSmallInteger('move_count')->nullable()->comment('이동 횟수');
      $table->string('seq_category')->comment('시퀀스 카테고리');
      $table->unsignedSmallInteger('end_point_area')->nullable()->comment('종료 지점');
      $table->unsignedSmallInteger('next_start_point')->nullable()->comment('다음 시작 지점');
      $table->boolean('highlight')->nullable()->comment('하이라이트 여부');
      $table->unsignedSmallInteger('highlight_count')->nullable()->comment('하이라이트 횟수');
      for ($n = 0; $n <= 16; $n++) {
        $table->jsonb('step' . $n)->nullable()->comment('좌표');
      }
      $table->string('event_split')->nullable()->comment('이벤트 명세');
      $table->string('highlight_check')->nullable()->comment('하이라이트 명세');

      foreach (['home', 'away'] as $team) {
        $ucFirst = Str::ucfirst($team);
        $table->unsignedfloat($team . '_possession', 3)->nullable()->comment($ucFirst . ' 점유율');
        $table->unsignedSmallInteger($team . '_highlight_count')->nullable()->comment($ucFirst . ' 하이라이트 수');
        $table->unsignedSmallInteger($team . '_shots')->nullable()->comment($ucFirst . ' 슈팅 수');
        $table->unsignedSmallInteger($team . '_shots_on_target')->nullable()->comment($ucFirst . ' 유효슈팅 수');
        $table->unsignedSmallInteger($team . '_cornerkick')->nullable()->comment($ucFirst . ' 코너킥 수');
        $table->unsignedSmallInteger($team . '_goal')->nullable()->comment($ucFirst . ' 골 수');
        $table->unsignedSmallInteger($team . '_foul_y')->nullable()->comment($ucFirst . ' 경고 수');
        $table->unsignedSmallInteger($team . '_foul_r')->nullable()->comment($ucFirst . ' 퇴장 수');
        $table->unsignedSmallInteger($team . '_foul')->nullable()->comment($ucFirst . ' 파울 수');
      }

      $table->timestamps();

      $table->index(['ref_scenario_id']);
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('ref_sequences');
  }
};

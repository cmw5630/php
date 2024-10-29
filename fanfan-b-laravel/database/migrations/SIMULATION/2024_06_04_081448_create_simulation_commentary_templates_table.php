<?php

use App\Enums\Simulation\SimulationCommentType;
use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('commentary_templates', function (Blueprint $table) {
      $table->id();
      $table->enum('type', ['event', 'ending'])->comment('분기인지 엔딩인지');
      $table->enum('name', SimulationCommentType::getValues())->comment('분기/엔딩 이름');
      $table->enum('timeline', ['goal', 'substitution', 'yellow_card', 'red_card'])->nullable()->comment('타임라인 표기타입');
      $table->text('comment')->comment('문자중계');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('commentary_templates');
  }
};

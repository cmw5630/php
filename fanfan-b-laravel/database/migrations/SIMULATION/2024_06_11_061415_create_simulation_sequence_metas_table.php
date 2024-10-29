<?php

use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationTeamSide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('sequence_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ref_sequence_id')->nullable()->constrained();
      $table->foreignUuid('schedule_id')->constrained();
      $table->enum('ending', SimulationEndingType::getValues());
      $table->enum('attack_direction', SimulationTeamSide::getValues())->nullable();
      $table->decimal('time_taken', 5, 2);
      $table->decimal('time_sum', 5, 2);
      $table->boolean('is_checked')->default(false);
      $table->jsonb('sequence_events');
      $table->timestamps();

      $table->unique(['schedule_id', 'ref_sequence_id']);
      $table->index('schedule_id', 'is_checked');
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('sequence_metas');
  }
};

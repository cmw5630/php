<?php

use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('steps', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sequence_meta_id')->constrained();
      $table->foreignId('commentary_template_id')->nullable()->constrained();
      $table->unsignedSmallInteger('seq_no');
      $table->unsignedInteger('playing_seconds')->default(0);
      $table->jsonb('coords')->nullable();
      $table->enum('event', SimulationEventType::getValues())->nullable();
      $table->jsonb('ref_params')->nullable();
      $table->boolean('is_highlight')->nullable();
      $table->json('highlight_overall')->nullable();
      $table->decimal('tick', 5, 2);
      $table->smallInteger('formation_place')->nullable();
      // $table->smallInteger('opposite_formation_place')->nullable(); // 우선 보류
      $table->smallInteger('home_goal');
      $table->smallInteger('away_goal');
      $table->boolean('is_last_step')->nullable()->default(false);
      $table->enum('ending', SimulationEndingType::getValues())->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('steps');
  }
};

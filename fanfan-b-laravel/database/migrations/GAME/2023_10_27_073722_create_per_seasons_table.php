<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('per_seasons', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->enum('position', PlayerPosition::getAllPositions())->nullable();
      $table->float('per_fp')->default(0);
      $table->integer('all_mins')->default(0);
      $table->timestamps();

      $table->unique(['season_id', 'position']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('per_seasons');
  }
};

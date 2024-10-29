<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\PlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('suspensions', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->foreignUuid('player_id')->constrained(PlateCard::getModel()->getTable(), 'player_id');
      $table->timestamp('suspension_start_date')->index();
      $table->timestamp('suspension_end_date')->nullable();
      $table->string('description')->nullable()->comment('결장 사유');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('suspensions');
  }
};

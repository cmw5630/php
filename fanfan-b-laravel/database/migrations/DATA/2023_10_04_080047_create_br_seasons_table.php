<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\BrLeague;
use App\Models\data\League;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('br_seasons', function (Blueprint $table) {
      $table->id();
      $table->string('season_name');
      $table->foreignUuid('opta_league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('opta_season_id')->constrained(Season::getModel()->getTable());
      $table->foreignId('br_league_id')->constrained(BrLeague::getModel()->getTable(), 'br_league_id');
      $table->unsignedBigInteger('br_season_id');
      $table->date('start_date');
      $table->date('end_date');
      $table->string('year', 9);
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('br_seasons');
  }
};

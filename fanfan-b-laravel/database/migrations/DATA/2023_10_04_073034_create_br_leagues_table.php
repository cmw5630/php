<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('br_leagues', function (Blueprint $table) {
      $table->id();
      $table->string('leagues_name');
      $table->unsignedBigInteger('br_league_id')->unique();
      $table->foreignUuid('opta_league_id')->unique()->constrained(League::getModel()->getTable());
      $table->string('country_name');
      $table->string('country_code')->nullable();
      $table->string('gender');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('br_leagues');
  }
};

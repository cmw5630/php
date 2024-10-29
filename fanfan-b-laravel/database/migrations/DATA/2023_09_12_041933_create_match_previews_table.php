<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('match_previews', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('home_team_id')->constrained(Team::getModel()->getTable());
      $table->foreignUuid('away_team_id')->constrained(Team::getModel()->getTable());
      $table->unsignedSmallInteger('home_team_wins');
      $table->unsignedSmallInteger('away_team_wins');
      $table->unsignedSmallInteger('draws');
      $table->unsignedSmallInteger('home_team_goals');
      $table->unsignedSmallInteger('away_team_goals');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('match_previews');
  }
};

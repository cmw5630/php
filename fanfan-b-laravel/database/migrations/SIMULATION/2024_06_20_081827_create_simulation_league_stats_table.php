<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\UserPlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('league_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('league_id')->constrained();
      $table->float('overall_avg')->nullable();
      $table->float('goal_avg')->nullable();
      $table->float('goal_against_avg')->nullable();
      $table->foreignId('best_goal_card')->nullable()->comment('골 1등 카드')->constrained(UserPlateCard::getModel()->getTable());
      $table->foreignId('best_assist_card')->nullable()->comment('어시 1등 카드')->constrained(UserPlateCard::getModel()->getTable());
      $table->foreignId('best_save_card')->nullable()->comment('세이브 1등 카드')->constrained(UserPlateCard::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('league_stats');
  }
};

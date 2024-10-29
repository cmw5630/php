<?php

use App\Enums\FantasyMeta\RefPlayerTierType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_league_tiers', function (Blueprint $table) {
      $table->id();
      $table->char('contient', 16)->comment('');
      $table->char('country', 36)->comment('');
      $table->char('country_id', 36)->comment('');
      $table->char('league_name', 36)->comment('');
      $table->char('league_id', 36)->comment('');
      $table->enum('tier', RefPlayerTierType::getValues())->comment('');

      $table->float('tier_quality')->comment('리그간 이동시 참조되는 티어 점수');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_league_tiers');
  }
};

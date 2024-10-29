<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('preset')->create('p_valid_schedule_stages', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('리그 id')->constrained(League::getModel()->getTable());
      $table->string('country')->comment('국가');
      $table->uuid('stage_format_id')->comment('국가');
      $table->string('stage_name')->comment('스테이지 이름');
      $table->string('week')->nullable()->comment('옵타 week');
      $table->smallInteger('match_count')->nullable()->comment('경기 수');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('preset')->dropIfExists('p_valid_schedule_stages');
  }
};

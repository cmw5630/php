<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_team_current_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->comment('시즌 id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('team_id')->comment('팀 id')->constrained(Team::getModel()->getTable());
      $table->char('main_formation_used')->comment('Team Main Formation Used');
      $table->jsonb('representative_player')->nullable()->comment('formation 별 대표선수');
      $table->jsonb('next_match_team')->nullable()->comment('다음 예정 경기 상대팀 정보');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_team_current_metas');
  }
};

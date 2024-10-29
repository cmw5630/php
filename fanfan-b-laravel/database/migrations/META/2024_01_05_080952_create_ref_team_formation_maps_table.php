<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_team_formation_maps', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('team_id')->comment('팀 id')->constrained(Team::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('선수 id')->constrained();
      $table->string('match_name', 100)->comment('경기 상 선수이름');
      $table->enum('position', PlayerPosition::getValues())->nullable()->comment('포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      $table->unsignedSmallInteger('1')->comment('formation_place 1번');
      $table->unsignedSmallInteger('2')->comment('formation_place 2번');
      $table->unsignedSmallInteger('3')->comment('formation_place 3번');
      $table->unsignedSmallInteger('4')->comment('formation_place 4번');
      $table->unsignedSmallInteger('5')->comment('formation_place 5번');
      $table->unsignedSmallInteger('6')->comment('formation_place 6번');
      $table->unsignedSmallInteger('7')->comment('formation_place 7번');
      $table->unsignedSmallInteger('8')->comment('formation_place 8번');
      $table->unsignedSmallInteger('9')->comment('formation_place 9번');
      $table->unsignedSmallInteger('10')->comment('formation_place 10번');
      $table->unsignedSmallInteger('11')->comment('formation_place 11번');
      $table->unsignedSmallInteger('all_count')->comment('전체 경기 출전 횟수');
      $table->float('fantasy_point_per')->default(0)->comment('판타지포인트 per90');
      $table->smallInteger('mins_played')->default(0)->comment('전체 플레이 시간');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_team_formation_maps');
  }
};

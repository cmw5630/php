<?php

use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_player_current_metas', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('선수 id')->constrained();
      $table->foreignId('plate_card_id')->comment('플레이트 카드 id')->constrained();
      $table->enum('position', PlayerPosition::getValues())->comment('플레이트 카드 기본 포지션')->constrained();

      $table->jsonb('last_5_matches')->nullable()->comment('선수의 마지막 5개 매치');
      $table->jsonb('last_team_match')->nullable()->comment('팀의 마지막 1개 매치');
      $table->json('last_home')->nullable()->comment('마지막 매치 home팀 정보');
      $table->json('last_away')->nullable()->comment('마지막 매치 away팀 정보');

      $table->foreignUuid('last_season_id')->nullable()->comment('선수의 마지막 참가 경기의 시즌 id')->constrained(Season::getModel()->getTable(), 'id');
      $table->foreignUuid('last_schedule_id')->nullable()->comment('선수의 마지막 참가 경기의 schedule id')->constrained(Schedule::getModel()->getTable(), 'id');
      //
      $table->foreignUuid('last_team_id')->nullable()->comment('선수의 마지막 참가 경기의 팀 id')->constrained(Team::getModel()->getTable(), 'id');
      // $table->string('last_team_name')->nullable()->comment('선수의 마지막 참가 경기의 팀 이름');
      // $table->string('last_team_short_name')->nullable()->comment('선수의 마지막 참가 경기의 팀 short 이름');
      $table->unsignedInteger('last_team_scores')->nullable()->comment('선수의 마지막 참가 경기가 스코어(소속팀)');
      //
      $table->foreignUuid('last_vs_team_id')->nullable()->comment('선수의 마지막 참가 경기의 상대 팀 id')->constrained(Team::getModel()->getTable(), 'id');
      // $table->string('last_vs_team_name')->nullable()->comment('선수의 마지막 참가 경기의 상대 팀 이름');
      // $table->string('last_vs_team_short_name')->nullable()->comment('선수의 마지막 참가 경기의 상대 팀 short 이름');
      $table->unsignedInteger('last_vs_team_scores')->nullable()->comment('선수의 마지막 참가 경기가 스코어(상대팀)');

      $table->float('last_player_fantasy_point')->nullable()->comment('마지막 경기 (만약 참가경기가 없다면 이전시즌의) 판타지포인트');
      $table->boolean('last_is_mom')->nullable()->comment('마지막 경기에서의 mom 여부');

      //
      // $table->unsignedInteger('last_lineup_total_count')->default(0)->comment('선수의 마지막 참가 경기가 속한 게임의 토탈 라인업 수');
      // $table->unsignedInteger('last_lineup_player_count')->default(0)->comment('선수의 마지막 참가 경기가 속한 게임의 라인업 수');
      //

      // center-->
      $table->timestamp('season_start_date');
      $table->foreignUuid('target_season_id')->nullable()->comment('집계 계산 시즌 id')->constrained(Season::getModel()->getTable(), 'id');
      $table->jsonb('formation_aggr')->nullable();
      $table->float('rating')->nullable()->comment('현재시즌 참가 레이팅 평균');
      $table->unsignedInteger('goals')->nullable()->comment('현재시즌 골 total');
      $table->unsignedInteger('assists')->nullable()->comment('참가 경기 어시스트 total');
      $table->unsignedInteger('clean_sheets')->nullable()->comment('참가 경기 클린시트 total');
      $table->unsignedInteger('saves')->nullable()->comment('참가 경기 선방 total');
      $table->json('grades')->nullable()->comment('등급 개수 테이블');
      // <--center

      // right-->
      $table->foreignUuid('target_league_id')->nullable()->comment('집계 계산 리그 id')->constrained(League::getModel()->getTable(), 'id');
      $table->string('target_league_code')->nullable()->comment('집계 계산 리그 code');
      // $table->string('current_league_name')->nullable()->comment('선수의 현재시즌의 리그 name');
      $table->string('target_season_name')->nullable()->comment('집계 계산 시즌 name');
      $table->unsignedSmallInteger('matches')->nullable()->comment('현재시즌 참가 경기 수');  // matches
      $table->float('player_fantasy_point_avg')->nullable()->comment('현재시즌(만약 참가경기가 없어도 이전 시즌 안봄) 판타지포인트 평균'); // Average
      $table->float('fantasy_top_rate')->nullable()->comment('선수의 판타지 포인트 평균 백분율'); //
      // upcomming 
      $table->foreignUuid('upcomming_schedule_id')->nullable()->comment('선수의 다음 경기 schedule_id')->constrained(Schedule::getModel()->getTable(), 'id');
      $table->timestamp('upcomming_started_at')->nullable()->comment('다음 경기 started_at');


      $table->json('upcomming_home')->nullable()->comment('다음 매치 home팀 정보');
      $table->json('upcomming_away')->nullable()->comment('다음 매치 away팀 정보');

      $table->foreignUuid('upcomming_team_id')->nullable()->comment('선수의 다음 참가 경기의 팀 id')->constrained(Team::getModel()->getTable(), 'id');
      // $table->string('upcomming_team_name')->nullable()->comment('다음 경기 team 이름');
      $table->foreignUuid('upcomming_vs_team_id')->nullable()->comment('선수의 다음 참가 경기의 상대 팀 id')->constrained(Team::getModel()->getTable(), 'id');
      // $table->string('upcomming_vs_team_name')->nullable()->comment('다음 경기 team 이름');
      $table->string('projection_point')->nullable()->comment('예상 판타지 포인트');
      // <--right

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_player_current_metas');
  }
};

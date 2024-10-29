<?php

use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create('user_plate_cards', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->foreignId('plate_card_id')->comment('플레이트카드 id')->constrained();
      $table->string('player_name')->comment('플레이어 이름');
      $table->foreignUuid('ref_player_overall_history_id')->comment('구매 당시 오버롤 id')->constrained(League::getModel()->getTable());
      $table->unsignedInteger('order_overall')->comment('구매 당시 선수의 최종 오버롤');
      $table->foreignUuid('order_league_id')->comment('구매 당시 league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('order_team_id')->comment('구매 당시 team_id')->constrained(Team::getModel()->getTable());
      $table->foreignUuid('draft_season_id')->nullable()->comment('강화완료 시즌id')->constrained(Season::getModel()->getTable());
      $table->string('draft_season_name', 10)->nullable()->comment('성공한 강화 season.name');
      $table->foreignUuid('draft_team_id')->nullable()->comment('강화완료 팀id')->constrained(Team::getModel()->getTable());
      $table->json('draft_team_names')->nullable()->comment('강화완료 팀 이름 json');
      $table->enum('lock_status', GradeCardLockStatus::getValues())->nullable()->comment('lock status');
      $table->unsignedTinyInteger('draft_schedule_round')->nullable()->comment('성공한 강화 schedule.round');
      $table->enum('draft_price_grade', OriginGrade::getValues())->nullable()->comment('강화완료시 가격등급');
      $table->integer('draft_shirt_number')->nullable()->comment('강화완료시 등 번호');
      $table->decimal('ingame_fantasy_point', 8, 1)->comment('강화 경기의 등급 판타지 포인트');
      $table->decimal('level_weight', 8, 1)->nullable()->comment('성공한 강화 레벨에 대한 가중치 포인트');
      $table->smallInteger('draft_level')->nullable()->comment('성공한 강화 수치');
      $table->smallInteger('attacking_level')->nullable()->comment('공격 레벨');
      $table->smallInteger('goalkeeping_level')->nullable()->comment('골키핑 레벨');
      $table->smallInteger('passing_level')->nullable()->comment('패스 레벨');
      $table->smallInteger('defensive_level')->nullable()->comment('수비 레벨');
      $table->smallInteger('duel_level')->nullable()->comment('병합 레벨');
      $table->jsonb('special_skills')->nullable()->comment('특수스킬');
      // $table->bigInteger('draft_point')->nullable()->comment('강화 포인트');
      // $table->enum('draft_point_type', PointType::getValues())->nullable()->comment('강화 포인트 타입');

      // 사용할수 없는 카드에 대해 새로운 카드로 교체시 순환참조할 수 있는 parent_id 필요.
      // 강화 관련 column 추후 추가
      $table->enum('card_grade', CardGrade::getValues())->index()->default(CardGrade::NONE)->comment('카드 등급');
      $table->enum('position', PlayerPosition::getValues())->comment('경기 포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      $table->enum('status', PlateCardStatus::getValues())->default(PlateCardStatus::PLATE)->comment('카드의 현재상태');
      $table->timestamp('draft_completed_at')->nullable()->comment('강화완료일시');
      $table->unsignedBigInteger('min_price')->comment('최소 거래 가격');
      $table->timestamp('burned_at')->nullable()->comment('소각일시');
      $table->boolean('is_open')->default(false)->comment('인게임 카드 오픈 여부');
      $table->boolean('is_free')->default(false)->comment('무료카드 여부');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('user_plate_cards');
    Schema::enableForeignKeyConstraints();
  }
};

<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\user\User;
use App\Models\user\UserPlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('draft_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id(최초 발행자)')->constrained(User::getModel()->getTable());
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 id')->constrained(UserPlateCard::getModel()->getTable())->onDelete('SET NULL')->onUpdate('cascade');
      // $table->foreignId('draft_selection_id')->comment('강화내역 id')->nullable()->constrained(new Expression($api . '.draft_selections'))->onDelete('SET NULL')->onUpdate('cascade');
      $table->foreignUuid('draft_season_id')->comment('강화당시시즌id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('draft_team_id')->comment('강화당시팀id')->constrained(Team::getModel()->getTable());
      $table->foreignUuid('schedule_id')->comment('스케쥴id')->constrained(Schedule::getModel()->getTable());
      // $table->string('player_name')->comment('선수 이름');
      $table->timestamp('origin_started_at')->comment('강화당시 경기 시작일');
      $table->enum('schedule_status', ScheduleStatus::getValues())->nullable()->comment('경기 상태');
      $table->enum('card_grade', CardGrade::getValues())->index()->default(CardGrade::NONE)->comment('카드 등급');
      // $table->smallInteger('card_grade_order')->comment('카드 등급 정렬 우선순위');
      $table->enum('status', DraftCardStatus::getValues())->index()->comment('카드 상태');
      // $table->smallInteger('status_order')->comment('카드 상태 정렬 우선순위');
      // $table->timestamp('upgrading_at')->comment('강화 첫 시도 날짜');
      // $table->timestamp('complete_at')->nullable()->comment('강화 완료 날짜');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('draft_logs');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

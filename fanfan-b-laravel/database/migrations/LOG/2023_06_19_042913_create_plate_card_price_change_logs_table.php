<?php

use App\Enums\Opta\Card\OriginGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\PlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('plate_card_price_change_logs', function (Blueprint $table) {
      $table->id();
      $table->timestamp('started_at')->nullable();
      $table->foreignUuid('player_id')->constrained(PlateCard::getModel()->getTable(), 'player_id');
      $table->string('player_name');
      $table->foreignId('plate_card_id')->constrained(PlateCard::getModel()->getTable());
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->string('league_name');
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->char('season_name', 10);
      $table->foreignUuid('schedule_id')->nullable()->comment('schedule id')->constrained(Schedule::getModel()->getTable());
      $table->decimal('current_normalized_v', 20, 16)->nullable()->comment('normalized_standard_v');
      $table->float('power_ranking_avg')->nullable()->comment('평균 파워랭킹');
      $table->enum('price_grade', OriginGrade::getValues())->comment('price_reset_schedule_id에서의 price grade');
      $table->smallInteger('opta_week')->nullable()->comment('옵타 week');

      $table->float('power_ranking')->nullable()->comment('(참고)사용 power_ranking');
      $table->float('normalized_personal')->nullable()->comment('(참고)이 경기의 player의 power ranking의 normalized value');
      $table->float('team_bonus')->nullable()->comment('(참고)팀 보너스');
      $table->integer('mins_played')->nullable()->comment('(참고)선수 mins_played');
      $table->boolean('is_change_spot')->default(false)->comment('price 변경 지점');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('plate_card_price_change_logs');
  }
};

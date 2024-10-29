<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('overalls', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 id')->constrained();
      $table->foreignId('user_plate_card_id')->nullable()->comment('사용자 카드 id')->constrained();
      $table->foreignUuid('season_id')->comment('강화완료 시즌id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('플레이어 id')->constrained();
      // 최종 오버롤(포지션 별 계수가 다름)
      $table->enum('sub_position', PlayerSubPosition::getValues())->comment('강화 당시의 세부 포지션');
      $table->enum('second_position', PlayerSubPosition::getValues())->nullable()->comment('강화 당시의 세컨드 포지션');
      $table->enum('third_position', PlayerSubPosition::getValues())->nullable()->comment('강화 당시의 써드 포지션');
      $table->jsonb('final_overalls')->comment('포지션 별 최종 오버롤');
      // attacking
      $table->unsignedInteger(SimulationCategoryType::ATTACKING . '_overall');
      $table->jsonb('shot')->nullable()->comment('슈팅');
      $table->jsonb('finishing')->nullable()->comment('결정력');
      $table->jsonb('dribbles')->nullable()->comment('드리블');
      $table->jsonb('positioning')->nullable()->comment('위치선정');
      // passing
      $table->unsignedInteger(SimulationCategoryType::PASSING . '_overall');
      $table->jsonb('passing')->nullable()->comment('패싱능력');
      $table->jsonb('chance_create')->nullable()->comment('기회창출');
      $table->jsonb('long_pass')->nullable()->comment('롱패스');
      $table->jsonb('crosses')->nullable()->comment('크로스');
      // defensive
      $table->unsignedInteger(SimulationCategoryType::DEFENSIVE . '_overall');
      $table->jsonb('tackles')->nullable()->comment('태클');
      $table->jsonb('blocks')->nullable()->comment('슈팅차단');
      $table->jsonb('clearances')->nullable()->comment('클리어링');
      $table->jsonb('instinct')->nullable()->comment('판단력');
      // duels
      $table->unsignedInteger(SimulationCategoryType::DUEL . '_overall');
      $table->jsonb('ground_duels')->nullable()->comment('지상 경합');
      $table->jsonb('aerial_duels')->nullable()->comment('공중볼 경합');
      $table->jsonb('interceptions')->nullable()->comment('인터셉트');
      $table->jsonb('recoveries')->nullable()->comment('적극성');
      // goalkeeping
      $table->unsignedInteger(SimulationCategoryType::GOALKEEPING . '_overall');
      $table->jsonb('saves')->nullable()->comment('선방능력');
      $table->jsonb('high_claims')->nullable()->comment('공중볼 처리');
      $table->jsonb('sweeper')->nullable()->comment('스위핑');
      $table->jsonb('punches')->nullable()->comment('펀칭');
      // physical
      $table->unsignedInteger(SimulationCategoryType::PHYSICAL . '_overall');
      $table->jsonb('speed')->nullable()->comment('스피드');
      $table->jsonb('balance')->nullable()->comment('밸런스');
      $table->jsonb('power')->nullable()->comment('파워');
      $table->jsonb('stamina')->nullable()->comment('스태미나');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('overalls');
  }
};

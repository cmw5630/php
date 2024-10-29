<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_player_overall_histories', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('schedule_id')->constrained(Schedule::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('선수 id')->constrained();
      $table->enum('position', PlayerPosition::getValues()); // Goalkeeper | Defender | 
      $table->enum('sub_position', PlayerSubPosition::getValues())->comment('세부 포지션');
      $table->enum('second_position', PlayerSubPosition::getValues())->comment('세부 포지션의 첫번째 세부 포지션');
      $table->enum('third_position', PlayerSubPosition::getValues())->comment('세부 포지션의 두번째 세부 포지션');
      $table->unsignedInteger('final_overall')->comment('최종 오버롤');
      $table->unsignedInteger('shot')->nullable()->comment('슈팅');
      $table->unsignedInteger('finishing')->nullable()->comment('결정력');
      $table->unsignedInteger('dribbles')->nullable()->comment('드리블');
      $table->unsignedInteger('positioning')->nullable()->comment('위치선정');
      $table->unsignedInteger('passing')->nullable()->comment('패싱능력');
      $table->unsignedInteger('chance_create')->nullable()->comment('기회창출');
      $table->unsignedInteger('long_pass')->nullable()->comment('롱패스');
      $table->unsignedInteger('crosses')->nullable()->comment('크로스');
      $table->unsignedInteger('tackles')->nullable()->comment('태클');
      $table->unsignedInteger('blocks')->nullable()->comment('슈팅차단');
      $table->unsignedInteger('clearances')->nullable()->comment('클리어링');
      $table->unsignedInteger('instinct')->nullable()->comment('판단력');
      $table->unsignedInteger('ground_duels')->nullable()->comment('지상 경합');
      $table->unsignedInteger('aerial_duels')->nullable()->comment('공중볼 경합');
      $table->unsignedInteger('interceptions')->nullable()->comment('인터셉트');
      $table->unsignedInteger('recoveries')->nullable()->comment('적극성');
      $table->unsignedInteger('saves')->nullable()->comment('선방능력');
      $table->unsignedInteger('high_claims')->nullable()->comment('공중볼 처리');
      $table->unsignedInteger('sweeper')->nullable()->comment('스위핑');
      $table->unsignedInteger('punches')->nullable()->comment('펀칭');
      $table->unsignedInteger('speed')->nullable()->comment('스피드');
      $table->unsignedInteger('balance')->nullable()->comment('밸런스');
      $table->unsignedInteger('power')->nullable()->comment('파워');
      $table->unsignedInteger('stamina')->nullable()->comment('스태미나');
      $table->boolean('is_current')->default(false)->comment('현재 선수 오버롤 여부');
      $table->timestamps();

      $table->unique(['schedule_id', 'player_id']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_player_overall_histories');
  }
};

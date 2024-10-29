<?php

use App\Enums\Opta\Player\PlayerSubPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Season;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_player_overalls', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('player_id')->comment('선수 id')->constrained();
      $table->enum('sub_position', PlayerSubPosition::getValues())->comment('세부 포지션');
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
      $table->timestamps();

      $table->unique(['season_id', 'player_id']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_player_overalls');
  }
};

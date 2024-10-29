<?php

use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Player\PlayerFoot;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\YesNo;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\meta\RefCountryCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('plate_cards', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->comment('플레이어 id')->constrained();
      $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
      $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
      $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());

      // $table->foreignId('squad_id')->constrained(env('DB_DATA_DATABASE') . '.' . 'squads');

      $table->tinyInteger('on_sale_manual')->default(1)->comment('(수동)카드 판매 여부');
      $table->float('plate_c_auto')->nullable()->comment('plate_c 자동 계산 결과');
      $table->enum('init_grade', OriginGrade::getValues())->nullable()->comment('현 시즌 카드 가격 최초 초기화 등급');
      $table->float('plate_c')->nullable()->comment('plate_c 수동 입력');
      $table->enum('grade', OriginGrade::getValues())->nullable()->comment('카드 가격 등급');
      $table->unsignedMediumInteger('price')->nullable();
      $table->foreignUuid('price_init_season_id')->nullable()->comment('가격 초기화가 적용된 시즌 id')->constrained(Season::getModel()->getTable());
      $table->enum('status', PlayerStatus::getValues());
      $table->enum('active', YesNo::getValues());
      // 삭제예정 column : first_name, last_name, short_first_name, short_last_name
      $table->string('first_name', 150);
      $table->string('first_name_eng', 150);
      $table->string('last_name', 150);
      $table->string('last_name_eng', 150);
      $table->string('short_first_name', 150)->nullable();
      $table->string('short_last_name', 150)->nullable();
      $table->string('match_name', 100)->comment('경기 상 선수이름');
      $table->string('match_name_eng', 100)->comment('경기 상 선수이름 영문화');
      $table->string('known_name', 150)->nullable();
      $table->unsignedSmallInteger('height')->nullable();
      $table->unsignedSmallInteger('weight')->nullable();
      $table->enum('foot', PlayerFoot::getValues())->nullable();
      $table->integer('shirt_number')->nullable()->comment('등 번호');
      $table->enum('position', PlayerPosition::getValues())->index(); // Goalkeeper | Defender | Midfielder | Attacker 주의 - Unknown 등 그 밖에 값은 insert 제외
      $table->char('nationality_code', 3)->nullable();
      $table->string('team_name', 100)->comment('팀 이름');
      $table->string('team_short_name', 50)->comment('팀 약칭');
      $table->string('team_club_name', 50)->comment('팀 클럽 이름');
      $table->string('team_code', 3)->comment('팀코드_3문자');
      $table->string('league_name', 100)->comment('리그 이름');
      $table->string('league_code', 3)->comment('리그코드');
      $table->string('headshot_path', 100)->nullable()->comment('헤드샷 경로');

      $table->timestamps();
      $table->unique('player_id');
    });
  }

  public function down()
  {
    Schema::dropIfExists('plate_cards');
  }
};

<?php

use App\Enums\Opta\BaseType;
use App\Enums\Opta\Player\PlayerFoot;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\Team\TeamType;
use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('squads', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->constrained(); // super 식별자 성격이 있음. unique key에 추가하지 않음.
      $table->foreignUuid('season_id')->constrained();
      $table->foreignUuid('team_id')->constrained();
      $table->uuid('player_id'); // player_id는 중복됨.
      $table->string('league_name', 100)->comment('리그 이름');
      $table->date('season_start_date')->comment('시즌 시작일');
      $table->date('season_end_date')->comment('시즌 종료일');
      $table->string('team_name', 100)->comment('팀 이름');
      $table->string('team_short_name', 50)->comment('팀 약칭');
      $table->string('team_club_name', 50)->comment('팀 클럽 이름');
      $table->string('team_code', 3)->comment('팀코드_3문자');
      $table->enum('squad_type', BaseType::getValues())->comment('스쿼드 타입:default|women|youth');
      $table->enum('team_type', TeamType::getValues())->comment('팀 타입:club|international');
      // $table->string('venue_name');
      // $table->string('venue_id');

      $table->string('match_name', 100)->comment('경기 상 선수이름');
      $table->string('nationality', 30)->comment('국가 이름');
      $table->uuid('nationality_id')->comment('국가 ID');

      //player 정보 ->
      $table->string('player_name', 150)->comment('first_name + last_name');

      $table->string('first_name', 150);
      $table->string('last_name', 150);
      $table->string('short_first_name', 150)->nullable();
      $table->string('short_last_name', 150)->nullable();
      $table->unsignedSmallInteger('height')->nullable();
      $table->unsignedSmallInteger('weight')->nullable();
      $table->enum('foot', PlayerFoot::getValues())->nullable();
      $table->string('known_name', 100)->nullable();
      $table->enum('position', PlayerPosition::getValues())->nullable()->comment('포지션'); // Goalkeeper | Defender | Midfielder | Attacker | Unknown | (empty option)
      $table->enum('type', PlayerType::getValues())->comment('타입'); // player | referee | coach | staff | assistant coach
      $table->date('date_of_birth')->nullable()->comment('출생일');
      $table->string('place_of_birth', 50)->nullable()->comment('출생지 지역 이름');
      $table->string('country_of_birth', 50)->comment('출생지 나라 이름');
      $table->uuid('country_of_birth_id')->comment('출생지 나라 ID');
      $table->integer('shirt_number')->nullable()->comment('등 번호');
      $table->enum('status', PlayerStatus::getValues())->comment('선수의 활동여부'); // active | retired | died
      $table->enum('active', YesNo::getValues())->comment('이 팀에서의 활동여부'); // yes | no
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();

      $table->unique(['season_id', 'team_id', 'player_id']);
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('squads');
  }
};

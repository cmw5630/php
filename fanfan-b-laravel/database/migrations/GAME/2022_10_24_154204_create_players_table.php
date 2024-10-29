<?php

use App\Enums\Opta\Player\PlayerFoot;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('players', function (Blueprint $table) {
      $table->uuid('id')->primary()->comment('player id');

      $table->string('match_name', 100)->comment('경기 상 선수이름');
      $table->string('nationality', 30)->comment('국가 이름');
      $table->uuid('nationality_id')->comment('국가 ID');

      //player 정보 ->
      $table->string('first_name', 150);
      $table->string('last_name', 150);
      $table->string('short_first_name', 100)->nullable();
      $table->string('short_last_name', 100)->nullable();
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
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('players');
    Schema::enableForeignKeyConstraints();
  }
};

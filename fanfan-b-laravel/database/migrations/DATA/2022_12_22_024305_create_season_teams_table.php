<?php

use App\Enums\Opta\BaseType;
use App\Enums\Opta\Team\TeamStatus;
use App\Enums\Opta\Team\TeamType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('season_teams', function (Blueprint $table) {
      $table->id()->comment('자동증가 키');
      $table->foreignUuid('season_id')->comment('시즌 id')->constrained();
      $table->foreignUuid('team_id')->comment('팀 id')->constrained();
      $table->string('name', 100)->comment('이름');
      $table->string('short_name', 50)->comment('약칭');
      $table->string('official_name', 50)->comment('공식명칭');
      $table->string('code', 3)->comment('팀코드_3문자');
      $table->enum('type', TeamType::getValues())->nullable()->comment('팀타입:club|international');
      $table->enum('team_type', BaseType::getValues())->nullable()->comment('타입:default|women|youth');
      $table->uuid('country_id')->nullable()->comment('국가 id');
      $table->string('country', 30)->nullable()->comment('국가이름');
      $table->enum('status', TeamStatus::getValues())->nullable()->comment('active|defunct');
      $table->string('city', 100)->nullable()->comment('팀베이스도시');
      // $table->string('postal_address', 100)->nullable()->comment('우편주소');
      // $table->string('address_zip', 20)->nullable()->comment('우편번호');
      // $table->string('founded', 100)->nullable()->comment('창단 연도'); // 창단연도가 이상한 데이터도 있음(ex:1904 / 1926 / 2004)
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('season_teams');
  }
};

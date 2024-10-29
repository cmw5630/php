<?php

use App\Enums\Opta\League\LeagueFormat;
use App\Enums\Opta\League\LeagueFormatType;
use App\Enums\Opta\League\LeagueStatusType;
use App\Enums\Opta\League\LeagueType;
use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('leagues', function (Blueprint $table) {
      $table->uuid('id')->primary()->comment('리그 id');
      $table->string('name', 100)->comment('이름');
      $table->string('league_code', 3)->comment('코드');
      $table->string('country', 30)->comment('개최 국가 이름');
      $table->uuid('country_id')->comment('개최 국가 ID');
      // 챔스 league_code 값이 아예 없어, front 에서 error 발생. default 값 설정.
      $table->string('country_code', 3)->nullable()->default('EU')->comment('개최 국가 코드'); // opta 명세상 not null이지만, 데이터가 없는 경우가 있음.
      $table->enum('is_friendly', YesNo::getValues())->comment('친선 경기여부'); // 친선? yes|no
      $table->enum('league_format', LeagueFormat::getValues())->comment('리그형식'); // Domestic league | Domestic cup | Domestic super cup | International cup | International super cup
      $table->enum('type', LeagueFormatType::getValues())->comment('리그형식 타입'); // men | women | youth
      $table->enum('league_type', LeagueType::getValues())->comment('리그타입'); // club | international
      // parsing 컬럼 설명-옵타 계약관련(계약이 종료된 리그는 'No'로 설정해야 필요없는 요청을 막을 수 있다.)
      $table->enum('is_opta_contracted', YesNo::getValues())->default(YesNo::YES)->comment('파싱 여부 조건부 검사-(parsing="No" and status="hide")일 때를 제외하고는 (MA2 등등) 모두 항상 파싱됨.');
      $table->enum('status', LeagueStatusType::getValues())->default(LeagueStatusType::HIDE)->comment('표출 방식');
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('leagues');
  }
};

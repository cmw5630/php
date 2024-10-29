<?php

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('fantasy_metas', function (Blueprint $table) {
      $table->id();
      $table->smallInteger('sync_order')->comment('동기화 순서');
      $table->enum('is_trigger', YesNo::getValues())->default(YesNo::NO)->comment('is trigger parser?');
      $table->enum('sync_group', FantasySyncGroupType::getValues())->comment('sync_group 이름');
      $table->char('sync_feed_nick', 32)->comment('meta data type');
      $table->char('description', 64)->comment('설명');
      $table->unsignedInteger('parsing_step')->default(0)->comment('파서 실행 동기화 참조 값');
      $table->unsignedInteger('parsing_count')->default(0)->comment('실제 파서 실행 수');
      $table->smallInteger('run_mins')->default(0)->comment('가장 최근 완료까지 걸린 시간(분)');
      $table->smallInteger('min_mins')->default(0)->comment('최소 시간(분)');
      $table->smallInteger('max_mins')->default(0)->comment('최대 시간(분)');
      $table->smallInteger('avg_mins')->default(0)->comment('평균 시간(분)');
      $table->enum('active', YesNo::getValues())->default(YesNo::NO)->comment('현재 파싱 중인지');
      $table->longText('extra_info')->nullable()->comment('json extra info');
      $table->string('class_name')->comment('실행할 클래스의 이름');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('fantasy_metas');
    Schema::enableForeignKeyConstraints();
  }
};

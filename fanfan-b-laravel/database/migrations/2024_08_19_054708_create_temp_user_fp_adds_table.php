<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\Schedule;

return new class extends Migration
{
  public function up()
  {
    Schema::create('temp_user_fp_adds', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 id')->constrained();
      $table->foreignUuid('schedule_id')->comment('스케쥴 ID')->constrained(Schedule::getModel()->getTable(), 'id');
      $table->float('category_point')->comment('category 추가 가산점');
      $table->jsonb('special_skills')->nullable()->comment('특수스킬');
      $table->float('special_skill_point')->comment('3강 달성 추가 가산점');
      $table->float('total_point')->comment('category+3강 추가 가산점');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('temp_user_fp_adds');
  }
};

<?php

use App\Enums\QuestCycleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Quest;

return new class extends Migration
{
  public function up()
  {
    Schema::create('quest_types', function (Blueprint $table) {
      $table->id();
      $table->foreignId('quest_id')->comment('퀘스트id')->constrained(Quest::getModel()->getTable());
      $table->unsignedSmallInteger('order_no')->comment('순서');
      $table->date('start_date')->comment('시작일');
      $table->date('end_date')->comment('종료일');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('quest_types');
  }
};

<?php

use App\Enums\QuestCollectionType;
use App\Enums\QuestCycleType;
use App\Enums\QuestRewardType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('quests', function (Blueprint $table) {
      $table->id();
      $table->enum('code', QuestCollectionType::getValues())->comment('퀘스트 코드');
      $table->string('name')->comment('퀘스트 내용');
      $table->unsignedInteger('achieve_count')->comment('달성 횟수');
      $table->enum('reward_type', QuestRewardType::getValues())->comment('보상 타입');
      $table->unsignedInteger('reward_amount')->comment('보상 액수');
      $table->smallInteger('order_no')->comment('순서');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('quests');
  }
};

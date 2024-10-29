<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;
use App\Models\game\QuestType;

return new class extends Migration
{
  public function up()
  {
    Schema::create('quest_user_achievements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 ID')->constrained(User::getModel()->getTable());
      $table->foreignId('quest_type_id')->comment('qust type ID')->constrained(QuestType::getModel()->getTable());
      $table->boolean('is_claimed')->default(false)->comment('보상여부');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('quest_user_achievements');
  }
};

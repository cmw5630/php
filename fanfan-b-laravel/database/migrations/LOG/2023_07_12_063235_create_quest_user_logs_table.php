<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\QuestType;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('quest_user_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 ID')->constrained(User::getModel()->getTable());
      $table->foreignId('quest_type_id')->comment('qust type ID')->constrained(QuestType::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('quest_user_logs');
  }
};

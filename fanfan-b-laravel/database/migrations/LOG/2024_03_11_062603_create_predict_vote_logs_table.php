<?php

use App\Models\game\PlateCard;
use App\Models\user\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('predict_vote_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('predict_vote_item_id')->constrained();
      $table->foreignId('user_id')->constrained(User::getModel()->getTable());
      $table->foreignId('answer')->nullable()->constrained(PlateCard::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('predict_vote_logs');
  }
};

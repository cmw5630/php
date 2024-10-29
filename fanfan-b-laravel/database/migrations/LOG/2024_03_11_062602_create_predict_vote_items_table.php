<?php

use App\Models\game\PlateCard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('predict_vote_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('predict_vote_id')->constrained();
      $table->foreignId('predict_vote_question_id')->constrained();
      $table->foreignId('option1')->comment('선택1 plate_card_id')->constrained(PlateCard::getModel()->getTable());
      $table->foreignId('option2')->comment('선택2 plate_card_id')->constrained(PlateCard::getModel()->getTable());
      $table->enum('answer', [1, 2])->nullable();
      $table->timestamps();

      // $table->unique(['predict_vote_id', 'predict_vote_question_id']);
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('predict_vote_items');
  }
};

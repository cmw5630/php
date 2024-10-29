<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('predict_vote_questions', function (Blueprint $table) {
      $table->id();
      $table->string('question');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('predict_vote_questions');
  }
};

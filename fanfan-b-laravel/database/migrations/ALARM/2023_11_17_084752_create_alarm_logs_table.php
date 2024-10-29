<?php

use App\Models\alarm\AlarmTemplate;
use App\Models\user\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('alarm_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained(User::getModel()->getTable());
      $table->string('alarm_template_id', 50);
      $table->json('dataset')->nullable();
      $table->timestamps();

      $table->foreign('alarm_template_id')->references('id')->on(AlarmTemplate::getModel()->getTable());
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('alarm_logs');
  }
};

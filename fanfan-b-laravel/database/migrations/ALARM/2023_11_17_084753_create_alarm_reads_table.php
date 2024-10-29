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
    Schema::connection('log')->create('alarm_reads', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained(User::getModel()->getTable());
      $table->foreignId('alarm_log_id')->constrained();
      $table->timestamps();

      $table->unique([
        'user_id',
        'alarm_log_id'
      ]);
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('alarm_reads');
  }
};

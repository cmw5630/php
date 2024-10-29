<?php

use App\Enums\EventPointType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('event_point_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->comment('사용자 ID')->constrained(User::getModel()->getTable());
      $table->integer('point')->comment('적립하는 이벤트포인트 금액');
      $table->enum('point_type', EventPointType::getValues())->index()->comment('이벤트포인트 적립타입');
      $table->string('description')->nullable()->comment('상세 내용');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('event_point_logs');
  }
};

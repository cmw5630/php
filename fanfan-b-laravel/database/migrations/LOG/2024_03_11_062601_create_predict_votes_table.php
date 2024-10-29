<?php

use App\Models\admin\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('predict_votes', function (Blueprint $table) {
      $table->id();
      $table->string('title', 100)->comment('운영용 설문 타이틀');
      $table->foreignId('admin_id')->constrained(Admin::getModel()->getTable());
      $table->timestamp('started_at')->default(now())->comment('시작 일시');
      $table->timestamp('ended_at')->comment('종료 일시');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('predict_votes');
  }
};

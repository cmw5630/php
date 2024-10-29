<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\admin\Admin;

return new class extends Migration
{
  public function up()
  {
    Schema::create('banners', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_id')->constrained(Admin::getModel()->getTable());
      $table->enum('platform', ['pc', 'mobile'])->commnt('플랫폼');
      $table->string('location', 5)->comment('위치 코드 B01');
      $table->string('file_name')->comment('배너 원 파일명');
      $table->string('image_path')->comment('배너 path');
      $table->string('link_url')->nullable()->comment('링크 url');
      $table->smallInteger('order_no')->default(1)->comment('순서');
      $table->timestamp('started_at')->comment('노출 시작일');
      $table->timestamp('ended_at')->comment('노출 종료일');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('banners');
  }
};

<?php

use App\Models\admin\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('post_attaches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained();
      $table->foreignId('admin_id')->nullable()->constrained(Admin::getModel()->getTable());
      $table->string('real_name');
      $table->string('file_name');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('post_attaches');
  }
};

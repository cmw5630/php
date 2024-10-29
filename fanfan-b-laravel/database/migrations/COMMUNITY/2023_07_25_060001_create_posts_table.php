<?php

use App\Enums\CommunityStatus;
use App\Models\admin\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('posts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_id')->nullable()->constrained(Admin::getModel()->getTable());
      $table->foreignId('user_id')->nullable()->constrained();
      $table->foreignId('board_id')->constrained();
      $table->foreignId('board_category_id')->nullable()->constrained();
      $table->string('title', 150);
      $table->text('content');
      $table->unsignedInteger('view_count')->default(0);
      $table->enum('status', CommunityStatus::getValues())->default('normal');
      $table->string('restricted_reason', 3)->nullable()->comment('제한 사유 R01');
      $table->foreignId('restricted_admin_id')->nullable()->constrained(Admin::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('posts');
  }
};

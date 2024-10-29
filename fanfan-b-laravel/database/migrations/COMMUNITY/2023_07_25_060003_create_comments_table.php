<?php

use App\Enums\CommunityStatus;
use App\Models\admin\Admin;
use App\Models\user\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained();
      $table->foreignId('post_id')->constrained();
      $table->foreignId('parent_comment_id')->nullable()->constrained('comments');
      $table->foreignId('mentioned_user_id')->nullable()->constrained(User::getModel()->getTable());
      $table->text('content');
      $table->json('attach_images')->comment('첨부 이미지');
      $table->enum('status', CommunityStatus::getValues())->default('normal');
      $table->string('restricted_reason', 3)->nullable()->comment('제한 사유 R01');
      $table->foreignId('restricted_admin_id')->nullable()->constrained(Admin::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('comments');
  }
};

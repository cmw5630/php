<?php

use App\Models\user\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('admin')->create('user_restrictions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('admin_id')->constrained();
      $table->foreignId('user_id')->constrained(User::getModel()->getTable());
      $table->string('reason')->comment('제한 사유 R01');
      $table->string('period')->comment('제한 기간 R02');
      $table->timestamp('until_at')->nullable()->comment('제한 만료 일시');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('admin')->dropIfExists('user_restrictions');
  }
};

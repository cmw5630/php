<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::create('alarm_templates', function (Blueprint $table) {
      $table->string('id', 50)->primary();
      $table->string('title')->nullable();
      $table->text('route')->nullable();
      $table->text('message');
      $table->string('description');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('alarm_templates');
  }
};

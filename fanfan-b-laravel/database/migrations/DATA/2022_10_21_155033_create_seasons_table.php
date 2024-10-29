<?php

use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('seasons', function (Blueprint $table) {
      $table->uuid('id')->primary()->comment('시즌 id');
      $table->foreignUuid('league_id')->comment('리그 id')->constrained();
      $table->enum('includes_venues', YesNo::getValues()); // 'yes' or 'no'
      $table->string('name', 10)->comment('이름');
      $table->date('start_date')->comment('시즌시작');
      $table->date('end_date')->comment('시즌종료');
      $table->enum('active', YesNo::getValues())->comment('yes|no'); // 'yes' or 'no'
      $table->enum('includes_standings', YesNo::getValues())->comment('가능한 standings phase 포함 여부, yes|no');
      $table->timestamp('last_updated')->nullable()->comment('최종 갱신 일시');

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('seasons');
  }
};

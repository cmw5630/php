<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;

return new class extends Migration
{
  public function up()
  {
    Schema::create('ref_transfer_values', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('league_id')->comment('이적 리그')->constrained(League::getModel()->getTable());
      $table->string('league_name')->comment('리그 이름');
      $table->string('league_country')->comment('리그 이름');
      $table->unsignedBigInteger('value')->comment('이적료 기준');
      $table->float('v_bonus')->comment('v_bonus');
      // $table->string('currency', 10)->comment('type과 value가 null이 아닐 때만 값이 있음');
      // $table->timestamp('announce_date')->comment('이적 발표일(UTC)');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('ref_transfer_values');
  }
};

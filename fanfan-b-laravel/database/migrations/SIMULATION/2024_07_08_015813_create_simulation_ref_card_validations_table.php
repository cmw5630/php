<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\UserPlateCard;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('simulation')->create('ref_card_validations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_plate_card_id')->comment('사용자 카드 id')->constrained(UserPlateCard::getModel()->getTable(), 'id');
      $table->jsonb('banned_schedules')->comment('출전 금지 스케쥴s');
      $table->unsignedTinyInteger('yellow_card_count');
      $table->unsignedTinyInteger('red_card_count');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('ref_card_validations');
  }
};

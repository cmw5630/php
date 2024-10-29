<?php

use App\Models\user\User;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('user_plate_card_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_plate_card_id')->constrained(UserPlateCard::getModel()->getTable());
      $table->foreignId('user_id')->constrained(User::getModel()->getTable());
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->dropIfExists('user_plate_card_logs');
  }
};

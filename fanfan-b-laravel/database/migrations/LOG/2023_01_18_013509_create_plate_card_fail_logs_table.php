<?php

use App\Enums\PlateCardFailLogType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\PlateCard;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('plate_card_fail_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('plate_card_id')->nullable()->constrained(PlateCard::getModel()->getTable())->onDelete('SET NULL');
      $table->foreignUuid('player_id')->nullable()->constrained(Player::getModel()->getTable())->onDelete('SET NULL');
      $table->enum('fail_type', PlateCardFailLogType::getValues());
      $table->boolean('done')->default(false)->comment('처리여부(운영)');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('plate_card_fail_logs');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

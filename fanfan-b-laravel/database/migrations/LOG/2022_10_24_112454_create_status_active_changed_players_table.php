<?php

use App\Enums\Opta\Squad\PlayerChangeStatus;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\YesNo;
use App\Models\data\Squad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('log')->create('status_active_changed_players', function (Blueprint $table) {
      $table->id();
      $table->foreignId('squads_id')->nullable()->constrained(Squad::getModel()->getTable())->onDelete('set null');
      $table->uuid('season_id');
      $table->uuid('team_id');
      $table->uuid('player_id');
      $table->enum('old_status', PlayerStatus::getValues())->nullable()->comment('이전 상태값');
      $table->enum('old_active', YesNo::getValues())->nullable()->comment('이전 active 값');
      $table->enum('status', PlayerStatus::getValues())->comment('현재 상태값');
      $table->enum('active', YesNo::getValues())->comment('현재 active 값');
      $table->enum('changed_type', PlayerChangeStatus::getValues());
      $table->timestamps();
    });
  }

  public function down()
  {

    Schema::connection('log')->disableForeignKeyConstraints();
    Schema::connection('log')->dropIfExists('status_active_changed_players');
    Schema::connection('log')->enableForeignKeyConstraints();
  }
};

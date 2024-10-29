<?php

use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\Transfer\TransferType;
use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('transfers', function (Blueprint $table) {
      $table->id();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable());
      // $table->string('first_name')->comment();
      // $table->string('last_name')->comment();
      // $table->string('short_first_name')->comment();
      // $table->string('short_last_name')->comment();
      // $table->string('match_name')->comment();
      $table->enum('player_type', PlayerType::getValues())->comment();
      $table->string('player_position')->comment('position summary');
      $table->string('nationality_id');
      $table->string('nationality');
      $table->string('membership_idx')->comment('정보 식별을 위한 인조키');
      $table->uuid('team_id')->comment('이적한 팀 id'); // foreign key 사용 x
      $table->string('team_name')->comment('이적한 팀 이름');
      $table->enum('active', YesNo::getValues())->comment('현재 활동 여부');
      $table->timestamp('membership_start_date')->comment('멤버쉽 시작일');
      $table->timestamp('membership_end_date')->nullable()->comment('멤버쉽 종료일');
      $table->enum('transfer_type', TransferType::getValues())->comment('이적 타입');
      $table->unsignedBigInteger('value')->nullable()->comment('이적 값');
      $table->string('currency')->nullable()->comment('통화');
      $table->timestamp('announced_date')->comment('이적 발표일');
      $table->uuid('from_team_id')->comment('이적 전 팀 id'); // foreign key 사용 x
      $table->string('from_team_name')->comment('이적 전 팀이름');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('transfers');
  }
};

<?php

use App\Enums\Opta\YesNo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration {
  public function up()
  {
    Schema::connection('simulation')->create('applicants', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->unique()->comment('사용자 id')->unique()->constrained(User::getModel()->getTable());
      $table->enum('server', array_keys(config('simulationpolicies')['server']))->default('asia')->comment('서버');
      $table->string('club_code_name', 3)->comment('클럽코드명');
      $table->enum('active', YesNo::getValues())->default(YesNo::YES)->comment('휴면상태 여부(yes: 참여가능 / no: 휴면상태)');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('simulation')->dropIfExists('applicants');
  }
};

<?php

use App\Models\data\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\user\User;

return new class extends Migration
{
  public function up()
  {
    Schema::create('user_metas', function (Blueprint $table) {
      $table->foreignId('user_id')->primary()->comment('사용자 id')->constrained();
      $table->string('photo_path', 100)->nullable()->comment('사용자의 프로필사진');
      $table->foreignUuid('favorite_team_id')->comment('선호 구단')->constrained(Team::getModel()->getTable());
      $table->boolean('optional_agree')->default(false)->comment('선택 약관');
      $table->boolean('is_pack_open')->default(false)->comment('웰컴팩 카드 오픈 여부');
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('user_metas');
  }
};

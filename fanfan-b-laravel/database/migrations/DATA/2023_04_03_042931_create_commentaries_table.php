<?php

use App\Enums\Opta\Commentary\CommentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\game\Player;

return new class extends Migration
{
  public function up()
  {
    Schema::connection('data')->create('commentaries', function (Blueprint $table) {
      $table->id('id');
      $table->string('comment_id', 36)->comment('message id');
      $table->foreignUuid('schedule_id')->constrained();
      $table->foreignUuid('league_id')->constrained();
      $table->foreignUuid('season_id')->constrained();
      $table->tinyInteger('number_of_periods')->nullable();
      $table->tinyInteger('period_length')->nullable();
      $table->timestamp('last_updated')->nullable();
      $table->string('description');
      $table->foreignUuid('home_team_id')->constrained('teams');
      $table->foreignUuid('away_team_id')->constrained('teams');
      $table->text('comment')->nullable()->comment();
      $table->timestamp('timestamp')->nullable()->comment();
      $table->timestamp('last_modified');
      $table->tinyInteger('minute')->nullable();
      $table->tinyInteger('period')->nullable();
      $table->tinyInteger('second')->nullable();
      $table->string('time_summary', 255)->nullable()->comment('opta key 이름 = time');
      $table->string('type'); // enum(CommentType) -> string으로 변경 
      $table->foreignUuid('player_ref1')->nullable()->constrained(Player::getModel()->getTable());
      $table->foreignUuid('player_ref2')->nullable()->constrained(Player::getModel()->getTable());
      $table->timestamps();

      $table->unique(['comment_id']);
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('commentaries');
  }
};

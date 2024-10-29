<?php

use App\Enums\Opta\Career\MembershipRole;
use App\Enums\Opta\Career\MembershipType;
use App\Enums\Opta\League\LeagueFormat;
use App\Enums\Opta\Player\PlayerFoot;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\Team\TeamType;
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
    Schema::connection('data')->create('player_careers', function (Blueprint $table) {
      // first_name
      // last_name
      // short_first_name
      // short_last_name
      // match_name
      $table->id();
      $table->foreignUuid('player_id')->constrained(Player::getModel()->getTable());
      $table->unsignedSmallInteger('group_no');
      $table->unsignedSmallInteger('membership_idx');
      $table->enum('player_type', PlayerType::getValues())->comment();
      $table->enum('player_position', PlayerPosition::getValues())->nullable();
      $table->timestamp('last_updated');
      $table->string('nationality');
      $table->uuid('nationality_id');
      $table->timestamp('date_of_birth')->nullable();
      $table->string('place_of_birth')->nullable();
      $table->uuid('country_of_birth_id')->nullable();
      $table->string('country_of_birth')->nullable();
      $table->unsignedSmallInteger('height')->nullable();
      $table->unsignedSmallInteger('weight')->nullable();
      $table->enum('foot', PlayerFoot::getValues())->nullable();
      $table->enum('status', PlayerStatus::getValues());
      $table->string('team_id');
      $table->enum('team_type', TeamType::getValues());
      $table->string('team_name');
      $table->enum('active', YesNo::getValues());
      $table->timestamp('membership_start_date');
      $table->timestamp('membership_end_date')->nullable();
      $table->enum('role', MembershipRole::getValues());
      $table->enum('membership_type', MembershipType::getValues());
      $table->enum('transfer_type', TransferType::getValues())->nullable();
      $table->string('league_id')->nullable();
      $table->string('league_name')->nullable();
      $table->string('season_id')->nullable();
      $table->enum('league_format', LeagueFormat::getValues())->nullable();
      $table->enum('is_friendly', YesNo::getValues())->nullable();
      $table->unsignedSmallInteger('goals')->default(0);
      $table->unsignedSmallInteger('appearances')->default(0);
      $table->unsignedSmallInteger('assists')->default(0);
      $table->unsignedSmallInteger('penalty_goals')->default(0);
      $table->unsignedSmallInteger('yellow_cards')->default(0);
      $table->unsignedSmallInteger('second_yellow_cards')->default(0);
      $table->unsignedSmallInteger('red_cards')->default(0);
      $table->unsignedSmallInteger('substitute_in')->default(0);
      $table->unsignedSmallInteger('substitute_out')->default(0);
      $table->unsignedSmallInteger('subs_on_bench')->default(0);
      $table->smallInteger('minutes_played')->default(0);
      $table->unsignedSmallInteger('shirt_number')->nullable();
      $table->string('known_name')->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::connection('data')->dropIfExists('player_careers');
  }
};

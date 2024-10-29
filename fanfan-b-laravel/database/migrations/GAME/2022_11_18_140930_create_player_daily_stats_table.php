<?php

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Libraries\Classes\Blueprint;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;

return new class extends Migration
{
  public function up()
  {
    Schema::create(
      'player_daily_stats',
      function (Blueprint $table) {
        $table->id();
        $table->foreignUuid('schedule_id')->constrained(Schedule::getModel()->getTable());
        $table->foreignUuid('league_id')->constrained(League::getModel()->getTable());
        $table->foreignUuid('season_id')->constrained(Season::getModel()->getTable());
        $table->foreignUuid('team_id')->constrained(Team::getModel()->getTable());
        $table->foreignUuid('player_id')->constrained();

        $table->enum('status', ScheduleStatus::getValues());
        $table->boolean('game_started')->default(false);
        $table->boolean('total_sub_on')->default(false);

        $table->enum('card_grade', CardGrade::getValues())->default(CardGrade::NONE);
        $table->float('fantasy_point')->default(0);
        $table->boolean('is_mom')->default(false)->comment('MOM 여부');
        $table->float('rating')->default(0);
        $table->float('point_c')->nullable();
        $table->float('rating_c')->nullable();
        $table->float('card_c')->nullable();
        $table->enum('position', PlayerDailyPosition::getValues())->nullable();
        $table->enum('sub_position', PlayerPosition::getValues())->nullable();
        $table->enum('summary_position', PlayerPosition::getValues());

        // PointCategoryType::OFFENSIVE
        $table->decimal('goals', 5)->default(0.0);
        $table->decimal('winning_goal', 5)->default(0.0);
        $table->decimal('att_freekick_goal', 5)->default(0.0);
        $table->decimal('ontarget_scoring_att', 5)->default(0.0);
        $table->decimal('goal_assist', 5)->default(0.0);
        $table->decimal('won_contest', 5)->default(0.0);
        $table->decimal('big_chance_missed', 5)->default(0.0);
        $table->decimal('penalty_missed',  5)->default(0.0); // 'att_pen_miss+att_pen_post+att_pen_target',
        $table->decimal('total_offside', 5)->default(0.0);
        // PointCategoryType::PASSING
        $table->decimal('total_att_assist', 5)->default(0.0);
        $table->decimal('big_chance_created', 5)->default(0.0);
        $table->decimal('final_third_entries', 5)->default(0.0);
        $table->decimal('accurate_cross', 5)->default(0.0);
        $table->decimal('accurate_long_balls', 5)->default(0.0);
        $table->decimal('pass_accuracy', 5)->default(0.0); // 'accurate_pass/total_pass',
        $table->decimal('missed_pass', 5)->default(0.0); // 'total_pass-accurate_pass',
        // PointCategoryType::DEFENSIVE
        $table->decimal('won_tackle', 5)->default(0.0);
        $table->decimal('outfielder_block', 5)->default(0.0);
        $table->decimal('effective_clearance', 5)->default(0.0);
        $table->decimal('offside_provoked', 5)->default(0.0);
        $table->decimal('fouls', 5)->default(0.0);
        $table->decimal('penalty_conceded', 5)->default(0.0);
        $table->decimal('clean_sheet', 5)->default(0.0);
        $table->decimal('goals_conceded', 5)->default(0.0);
        $table->decimal('error_lead_to_shot', 5)->default(0.0);
        $table->decimal('error_lead_to_goal', 5)->default(0.0);
        // PointCategoryType::DUEL
        $table->decimal('ball_recovery', 5)->default(0.0);
        $table->decimal('interception', 5)->default(0.0);
        $table->decimal('penalty_won', 5)->default(0.0);
        $table->decimal('duel_won', 5)->default(0.0);
        $table->decimal('duel_lost', 5)->default(0.0);
        $table->decimal('ground_duel_won', 5)->default(0.0); // 'duel_won-aerial_won',
        $table->decimal('ground_duel_lost', 5)->default(0.0); // 'duel_lost-aerial_lost',
        $table->decimal('aerial_won', 5)->default(0.0);
        $table->decimal('aerial_lost', 5)->default(0.0);
        // PointCategoryType::GOALKEEPING
        $table->decimal('saves', 5)->default(0.0);
        $table->decimal('saved_ibox', 5)->default(0.0);
        $table->decimal('penalty_save', 5)->default(0.0);
        $table->decimal('good_high_claim', 5)->default(0.0);
        $table->decimal('dive_catch', 5)->default(0.0);
        $table->decimal('punches', 5)->default(0.0);
        $table->decimal('accurate_keeper_sweeper', 5)->default(0.0);
        // PointCategoryType::GENERAL
        $table->decimal('mins_played', 5)->default(0.0);
        $table->decimal('own_goals', 5)->default(0.0);
        $table->decimal('yellow_card', 5)->default(0.0);
        $table->decimal('red_card', 5)->default(0.0);
        //
        $table->timestamps();
        $table->index(['schedule_id', 'player_id']);
      }
    );
  }

  public function down()
  {
    Schema::dropIfExists('player_daily_stats');
  }
};

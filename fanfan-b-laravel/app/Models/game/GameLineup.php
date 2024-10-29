<?php

namespace App\Models\game;

use App\Models\data\Schedule;
use App\Models\data\Team;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameLineup extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'm_fantasy_point' => 'float',
    'special_skills' => 'array',
  ];

  // public function scopeIsThereGameActivated($query, int $_userPlateCardId, string|null $_currentScheduleId, int|null $_gameJoinId)
  // {
  //   // $_currentScheduleId 현재 종료될 live schedule_id, null이면 games 테이블을 바로 확인(admin 처리)
  //   if ($_currentScheduleId && $_gameJoinId) {
  //     $currentGameId = $query->clone()
  //       ->where('user_plate_card_id', $_userPlateCardId)
  //       ->where('schedule_id', $_currentScheduleId)
  //       ->where('game_join_id', $_gameJoinId)
  //       ->first()->gameJoin->game_id;

  //     // 
  //     $isCurrentGameNotFinished = GameSchedule::where('game_id', $currentGameId)
  //       ->whereNot('schedule_id', $_currentScheduleId) // 현재 schedule은 종료될 예정
  //       ->whereNotIn(
  //         'status',
  //         [ScheduleStatus::PLAYED, ScheduleStatus::CANCELLED, ScheduleStatus::POSTPONED, ScheduleStatus::SUSPENDED]
  //       )
  //       ->exists();

  //     if ($isCurrentGameNotFinished) {
  //       return true;
  //     }
  //   }


  //   // $isThereOtherGames = $query->where('user_plate_card_id', $_userPlateCardId)
  //   //   ->withWhereHas('gameJoin.game', function ($query) {
  //   //     return $query->isEnded(false);
  //   //   })->exists();

  //   // ->get()->map(function ($item) {
  //   //   $item->gameJoin->game
  //   // });




  //   return $query->where('user_plate_card_id', $_userPlateCardId)
  //     ->whereHas('gameJoin.game', function ($query) use ($currentGameId) {
  //       $query->when($currentGameId !== null, function ($gameQuery) use ($currentGameId) {
  //         return $gameQuery->whereNot('id', $currentGameId);
  //       })->whereNull('completed_at');
  //     })->exists();
  // }


  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  // public function game()
  // {
  //   return $this->belongsTo(Game::class);
  // }

  public function gameJoin()
  {
    return $this->belongsTo(GameJoin::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function gamePossibleSchedule()
  {
    return $this->belongsTo(GamePossibleSchedule::class, 'schedule_id', 'schedule_id');
  }


  // public function gameSchedule()
  // {
  //   return $this->belongsTo(GameSchedule::class, 'schedule_id', 'schedule_id');
  // }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id');
  }

  public function plateCardWithTrashed()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function playerDailyStat()
  {
    return $this->hasOne(PlayerDailyStat::class, 'schedule_id', 'schedule_id')
      ->where('player_id', $this->player_id);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function changeTeam()
  {
    return $this->belongsTo(Team::class, 'changed_team_id');
  }
}

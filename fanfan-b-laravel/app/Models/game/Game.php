<?php

namespace App\Models\game;

use App\Enums\GameType;
use App\Models\data\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_popular' => 'boolean',
  ];

  public function scopeIsEnded(Builder $query, $_isEnded = true, $_onlyCurrentSeason = true)
  {
    // 주의: game_schedules 상태 상관없이 games 의 completed_at을 본다.
    return $query->whereHas('season', function ($innerQuery) use ($_onlyCurrentSeason) {
      $innerQuery->currentSeasons($_onlyCurrentSeason);
    })->when(
      $_isEnded,
      function ($query) {
        $query->whereNotNull('completed_at');
      },
      function ($query) {
        $query->whereNull('completed_at');
      }
    );
  }

  public function scopeIsThereAnotherProceedingGame($query, int $_userPlateCardId, string $_targetScheduleId)
  {
    /*
    * $_userPlateCardId로 참가한 게임들의 진행 여부.
    */
    return $query->isEnded(false)
      ->whereHas('gameJoin', function ($fQuery) use ($_userPlateCardId, $_targetScheduleId) {
        $fQuery->withoutGlobalScopes()->whereHas('gameLineups', function ($query) use ($_userPlateCardId, $_targetScheduleId) {
          $query->where('user_plate_card_id', $_userPlateCardId)->whereNot('schedule_id', $_targetScheduleId);
        });
      })->exists();
  }

  public function scopeIsFreeGame($query)
  {
    return $query->whereIn('mode', [GameType::FREE, GameType::SPONSOR]);
  }

  public function scopeIsIngameLockReleased($query, $_isChecked = true)
  {
    return $query->where('is_ingame_lock_released', $_isChecked);
  }


  public function gameJoin()
  {
    return $this->hasMany(GameJoin::class);
  }

  public function gameSchedule()
  {
    return $this->hasMany(GameSchedule::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}

<?php

namespace App\Models\game;

use App\Casts\CorrectFloatPoint;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Models\data\Schedule;
use App\Models\data\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerDailyStat extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'rating' => CorrectFloatPoint::class . ':1',
    'fantasy_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::GENERAL . '_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::OFFENSIVE . '_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::DEFENSIVE . '_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::PASSING . '_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::DUEL . '_point' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::GOALKEEPING . '_point' => CorrectFloatPoint::class . ':1',
    'point_c' => CorrectFloatPoint::class . ':1',
    'rating_c' => CorrectFloatPoint::class . ':1',
    'is_mom' => 'boolean',
  ];

  public function scopeGameParticipantPlayer(Builder $_query)
  {
    return $_query->where(function ($query) {
      $query->where('game_started', true)->orWhere('total_sub_on', true);
    });
  }

  public function scopePlayedInSeason(Builder $_query, $_seasonId, $_round = null)
  {
    return $_query->whereHas('schedule', function ($query) use ($_seasonId, $_round) {
      $query->where([
        'season_id' => $_seasonId,
        'status' => ScheduleStatus::PLAYED,
      ])
        ->when($_round, function ($roundQuery, $round) {
          $roundQuery->where('round', $round);
        });
    })
      ->gameParticipantPlayer();
  }

  public static function getStatConditionalSeason($_seasonId, $_playerId)
  {
    if (!PlayerDailyStat::playedInSeason($_seasonId)->where('player_id', $_playerId)->exists()) {
      $_seasonId =  Season::getBeforeCurrentMapCollection()->keyBy('current_id')[$_seasonId]['before_id'];
      if (!PlayerDailyStat::playedInSeason($_seasonId)->where('player_id', $_playerId)->exists()) {
        return null;
      }
    }
    return $_seasonId;
  }

  public function player()
  {
    return $this->belongsTo(Player::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function draftSelection()
  {
    return $this->hasOne(DraftSelection::class, 'schedule_id', 'schedule_id');
  }
}

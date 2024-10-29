<?php

namespace App\Models\data;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\YesNo;
use App\Models\game\DraftSelection;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\PlayerDailyStat;
use App\Models\log\DraftLog;
use App\Models\log\LiveLog;
use App\Models\log\ScheduleVote;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $keyType = 'string';

  public $incrementing = false;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'undecided' => 'boolean',
  ];

  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope('serviceSchedule', function (Builder $builder) {
      $builder->where(function ($query) {
        $query->where('coverage_level', '>=', 13)
          ->orWhere([
            ['season_id', '4ebkg0znzhb9yxq6yr7ke2rkk'],
            ['coverage_level', '>=', 10]
          ]);
      });
    });
    static::addGlobalScope('withoutUnrealSchedule', function (Builder $builder) {
      $builder->whereNot('id', 'like', config('constant.UNREAL_SCHEDULE_PREFIX') . '%');
    });
  }

  public function scopeWithUnrealSchedule($query)
  {
    return $query->withoutGlobalScope('withoutUnrealSchedule');
  }

  public function scopeOnlyUnrealSchedule($query)
  {
    return $query
      ->withoutGlobalScope('withoutUnrealSchedule')
      ->where('id', 'like', config('constant.UNREAL_SCHEDULE_PREFIX') . '%');
  }

  public function scopeCurrentSeasonSchedules($query)
  {
    return $query->withWhereHas('season', function ($_query) {
      $_query->where('active', YesNo::YES);
    });
  }


  public function scopePossibleDraft($query, $param)
  {
    return $query->where([
      ['status', ScheduleStatus::FIXTURE],
      ['started_at', '>=', now()->addMinutes($param)]
    ]);
  }

  public function league()
  {
    return $this->belongsTo(League::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function home()
  {
    return $this->belongsTo(Team::class, 'home_team_id', 'id');
  }

  public function away()
  {
    return $this->belongsTo(Team::class, 'away_team_id', 'id');
  }

  public function draftLog()
  {
    return $this->hasMany(DraftLog::class);
  }

  public function draftSelection()
  {
    return $this->hasMany(DraftSelection::class);
  }

  // public function gameSchedule()
  // {
  //   return $this->hasMany(GameSchedule::class);
  // }

  public function playerDailyStat()
  {
    return $this->hasMany(PlayerDailyStat::class);
  }

  public function oneOptaPlayerDailyStat()
  {
    return $this->hasOne(OptaPlayerDailyStat::class);
  }

  public function optaPlayerDailyStat()
  {
    return $this->hasMany(OptaPlayerDailyStat::class);
  }

  public function onePlayerDailyStat()
  {
    return $this->hasOne(PlayerDailyStat::class);
  }

  public function gamePossibleSchedule()
  {
    return $this->hasOne(GamePossibleSchedule::class);
  }

  public function scheduleVote()
  {
    return $this->hasOne(ScheduleVote::class);
  }

  public function liveLog()
  {
    return $this->hasMany(LiveLog::class, 'unreal_schedule_id', 'id');
  }

  public function optaTeamDailyStat()
  {
    return $this->hasMany(OptaTeamDailyStat::class);
  }
}

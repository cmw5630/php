<?php

namespace App\Models\simulation;

use App\Enums\Simulation\SimulationScheduleStatus;
use App\Libraries\Traits\StaticTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationSchedule extends Model
{
  use SoftDeletes, StaticTrait;

  protected $table = 'schedules';

  protected $connection = 'simulation';

  public $incrementing = false;

  protected $keyType = 'string';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'started_at' => 'datetime',
    'is_rank_completed' => 'boolean',
  ];

  public static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      $model->id = self::getUuid($model);
    });
  }
  /**
   * @param Builder $query 
   * @return Builder 
   * gameOver 기준 30분 경과
   */
  // public function scopeNextSchedule(Builder $query)
  // {
  //   return $query->where('is_sim_ready', false)->whereBetween('started_at', [now()->subMinutes(30), now()->addMinutes(10)]);
  // }

  public function scopeCurrentUserLineupNotLocked(Builder $query)
  {
    // user_lineup -> lineup 복사 가능 flag값 변환
    return $query->where(
      [
        'status' => SimulationScheduleStatus::FIXTURE,
        'is_user_lineup_locked' => false,
        'is_next_lineup_ready' => false,
        'is_sim_ready' => false,
      ]
    )->where('started_at', '<', now()->addMinutes(10));
  }

  public function scopeCurrentUserLineupLocked(Builder $query)
  {
    // user_lineup -> lineup 복사 조건
    return $query->where(
      [
        'is_user_lineup_locked' => true,
        'is_next_lineup_ready' => false,
        'is_sim_ready' => false,
      ]
    )->whereBetween('started_at', [now()->subMinutes(30), now()->addMinutes(10)]);
  }

  public function scopePlayings(Builder $query)
  {
    // 경기 시작 30분 이후 경기는 Playing 상태로 안본다.
    return $query->where([
      'status' => SimulationScheduleStatus::PLAYING,
      'is_sim_ready' => true,
    ])
      ->whereBetween('started_at', [now()->subMinutes(30), now()]);
  }

  public function scopeGameStartedFixture(Builder $query)
  {
    // 경기 시작 Fixture 상태
    return $query->where([
      'status' => SimulationScheduleStatus::FIXTURE,
      'is_sim_ready' => true,
    ])->whereBetween('started_at', [now()->subMinutes(30), now()]);
  }

  public function scopeLiveEndedPlaying(Builder $query)
  {
    // live 정상완료된  Playing 상태
    return $query
      ->where('status', SimulationScheduleStatus::PLAYING)
      ->where(function ($_innerQueryOne) {
        $_innerQueryOne
          ->where('started_at', '<=', now()->subMinutes(30))
          ->orWhere(function ($_innerQueryOne) {
            $_innerQueryOne
              ->where('started_at', '>', now()->subMinutes(30))
              ->whereDoesntHave('sequenceMeta', function ($_query) {
                $_query->where('is_checked', false);
              });
          });
      });
  }

  public function scopeGameOverNotReady(Builder $query)
  {
    // 경기 시작 시간이 30분 지났지만 Played 상태처리가 안된 시나리오 분리작업이 안된 스케쥴
    return $query
      ->where(
        [
          'is_user_lineup_locked' => true,
          'is_next_lineup_ready' => false,
          'is_sim_ready' => false,
          ['started_at', '<', now()->subMinutes(30)->subSecond()]
        ]
      );
  }

  public function scopeGameOverReady(Builder $query)
  {
    // 경기 시작 시간이 30분 지났지만 Played 상태처리가 안된 (Playing 상태)의 시나리오 분리작업이 완료된 스케쥴
    return $query
      ->where(
        [
          'is_user_lineup_locked' => true,
          'is_next_lineup_ready' => true,
          'is_sim_ready' => true,
          ['started_at', '<', now()->subMinutes(30)->subSecond()]
        ]
      )
      ->whereNot('status', SimulationScheduleStatus::PLAYED);
  }

  public function season()
  {
    return $this->belongsTo(SimulationSeason::class);
  }

  public function lineupMeta()
  {
    return $this->hasMany(SimulationLineupMeta::class, 'schedule_id');
  }

  public function league()
  {
    return $this->belongsTo(SimulationLeague::class, 'league_id');
  }

  public function sequenceMeta()
  {
    return $this->hasMany(SimulationSequenceMeta::class, 'schedule_id');
  }

  public function lastSequenceMeta()
  {
    return $this->hasMany(SimulationSequenceMeta::class, 'schedule_id')->whereNotNull('attack_direction')->orderByDesc('id')->limit(1);
  }

  public function home()
  {
    return $this->belongsTo(SimulationApplicant::class, 'home_applicant_id');
  }

  public function away()
  {
    return $this->belongsTo(SimulationApplicant::class, 'away_applicant_id');
  }

  public function leagueStat()
  {
    return $this->belongsTo(SimulationLeagueStat::class, 'league_id', 'league_id');
  }
}

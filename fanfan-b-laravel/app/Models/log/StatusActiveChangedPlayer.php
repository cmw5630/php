<?php

namespace App\Models\log;

use App\Models\data\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class StatusActiveChangedPlayer extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeGetSameLogForSquad(Builder $_query, array $_squadRow)
  {
    // 동일한 로그가 이미 상태로그 테이블에 생성되어있는지
    return $_query->withTrashed()
      ->where('season_id', $_squadRow['season_id'])
      ->where('team_id', $_squadRow['team_id'])
      ->where('player_id', $_squadRow['player_id'])
      ->where('status', $_squadRow['status'])
      ->where('active', $_squadRow['active']);
  }

  public function scopeWithinCurrentSeason(Builder $_query)
  {
    return $_query->whereIn('season_id', Season::where('active', 'yes')->pluck('id')->toArray());
  }
}

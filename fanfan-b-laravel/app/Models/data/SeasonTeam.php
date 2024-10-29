<?php

namespace App\Models\data;

use App\Casts\CustomTeamName;
use App\Enums\Opta\YesNo;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeasonTeam extends Model
{
  use SoftDeletes;
  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'short_name' => CustomTeamName::class
  ];

  public function scopeCurrentSeason($_query)
  {
    return $_query->whereHas('season', function ($query) {
      $query->where('active', YesNo::YES);
    });
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }
}

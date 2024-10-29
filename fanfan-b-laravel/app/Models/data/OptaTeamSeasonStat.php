<?php

namespace App\Models\data;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptaTeamSeasonStat extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}

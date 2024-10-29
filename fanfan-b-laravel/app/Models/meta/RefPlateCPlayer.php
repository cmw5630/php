<?php

namespace App\Models\meta;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefPlateCPlayer extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeWithoutChampionsLeague(Builder $query)
  {
    $query->whereNot('league_id', config('constant.LEAGUE_CODE.UCL'));
  }


  protected $casts = [
    'power_ranking_avg' => CorrectFloatPoint::class . ':1',
  ];
}

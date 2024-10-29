<?php

namespace App\Models\meta;

use App\Casts\CorrectFloatPoint;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefTeamAggregation extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'avg_plus_goals' => CorrectFloatPoint::class . ':2',
    'avg_minus_goals' => CorrectFloatPoint::class . ':2',
    'max_avg_plus_goals' => CorrectFloatPoint::class . ':2',
    'max_avg_minus_goals' => CorrectFloatPoint::class . ':2',
  ];
}

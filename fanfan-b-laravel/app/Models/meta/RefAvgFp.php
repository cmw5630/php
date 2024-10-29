<?php

namespace App\Models\meta;

use App\Casts\CorrectFloatPoint;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Models\data\Season;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefAvgFp extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'fantasy_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::GENERAL . '_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::OFFENSIVE . '_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::DEFENSIVE . '_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::PASSING . '_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::DUEL . '_point_avg' => CorrectFloatPoint::class . ':1',
    FantasyPointCategoryType::GOALKEEPING . '_point_avg' => CorrectFloatPoint::class . ':1',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}

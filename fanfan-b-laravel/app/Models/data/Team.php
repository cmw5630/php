<?php

namespace App\Models\data;

use App\Casts\CustomTeamName;
use Model;
use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
  use SoftDeletes;
  protected $connection = 'data';

  public $incrementing = false;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'short_name' => CustomTeamName::class,
    'color' => 'array'
  ];

  public function plateCard()
  {
    return $this->hasMany(PlateCard::class);
  }

  public function seasonTeam()
  {
    return $this->hasMany(SeasonTeam::class);
  }
}

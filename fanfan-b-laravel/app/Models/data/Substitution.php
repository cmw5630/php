<?php

namespace App\Models\data;

use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Substitution extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function onPlateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_on_id')->withTrashed();
  }

  public function offPlateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_off_id')->withTrashed();
  }

  public function getSlotAttribute($value)
  {
    return $value === null ? null : chr($value + 97);
  }
}

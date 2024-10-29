<?php

namespace App\Models\data;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class EventGoal extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function getSlotAttribute($value)
  {
    return $value === null ? null : chr($value + 97);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }
}

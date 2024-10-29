<?php

namespace App\Models\log;

use App\Models\data\League;
use App\Models\data\Schedule;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'live_data' => 'array'
  ];

  public function league()
  {
    return $this->belongsTo(League::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }
}

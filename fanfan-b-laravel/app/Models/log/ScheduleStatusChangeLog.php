<?php

namespace App\Models\log;

use App\Models\data\Schedule;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class ScheduleStatusChangeLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

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
}

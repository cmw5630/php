<?php

namespace App\Models\log;

use App\Models\data\Schedule;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DraftLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    // 'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function scheduleStatusChangeLog()
  {
    return $this->hasMany(ScheduleStatusChangeLog::class, 'schedule_id', 'schedule_id');
  }
}

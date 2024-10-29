<?php

namespace App\Models\alarm;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class AlarmLog extends Model
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
    'dataset' => 'array',
    'is_read' => 'boolean',
  ];

  public function alarmTemplate()
  {
    return $this->belongsTo(AlarmTemplate::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function alarmRead()
  {
    return $this->hasOne(AlarmRead::class);
  }
}

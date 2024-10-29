<?php

namespace App\Models\game;

use App\Models\data\Schedule;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class DraftSelection extends Model
{
  use SoftDeletes;

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

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function draftLog()
  {
    return $this->hasMany(UserPlateCard::class);
  }
}

<?php

namespace App\Models\game;

use App\Models\meta\RefCountryCode;
use App\Models\Scopes\AddPlayerNameScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Player extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $keyType = 'string';

  public $incrementing = false;

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function plateCard()
  {
    return $this->hasOne(PlateCard::class);
  }

  public function playerDailyStat()
  {
    return $this->hasMany(PlayerDailyStat::class);
  }

  public function refCountryCode()
  {
    return $this->hasOne(RefCountryCode::class, 'nationality_id', 'nationality_id');
  }
}

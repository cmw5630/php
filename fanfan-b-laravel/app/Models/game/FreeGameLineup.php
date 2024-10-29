<?php

namespace App\Models\game;

use App\Models\data\Schedule;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class FreeGameLineup extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'special_skills' => 'array',
    'final_overall' => 'array',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class);
  }

  public function plateCardWithTrashed()
  {
    return $this->belongsTo(PlateCard::class, 'plate_card_id', 'id')->withTrashed();
  }

  public function gameJoin()
  {
    return $this->belongsTo(GameJoin::class);
  }
  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }
}

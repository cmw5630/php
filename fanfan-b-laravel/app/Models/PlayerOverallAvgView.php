<?php

namespace App\Models;

use App\Models\data\Season;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerOverallAvgView extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}

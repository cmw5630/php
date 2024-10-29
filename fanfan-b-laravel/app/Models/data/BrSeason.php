<?php

namespace App\Models\data;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class BrSeason extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeOptaCurrentSeason($query)
  {
    return $query->whereHas('optaSeason', function ($_query) {
      return $_query->currentSeasons();
    });
  }



  public function optaSeason()
  {
    return $this->belongsTo(Season::class, 'opta_season_id');
  }
}

<?php

namespace App\Models\meta;

use App\Models\data\Season;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefPlateCardRank extends Model
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

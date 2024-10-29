<?php

namespace App\Models\meta;

use App\Models\data\Season;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefCardcQuantile extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];


  public function playingSeason()
  {
    return $this->belongsTo(Season::class);
  }
}

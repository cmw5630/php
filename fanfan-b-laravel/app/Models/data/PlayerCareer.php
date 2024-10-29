<?php

namespace App\Models\data;

use App\Models\meta\RefCountryCode;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PlayerCareer extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function countryCode()
  {
    return $this->belongsTo(RefCountryCode::class, 'nationality_id', 'nationality_id');
  }
}

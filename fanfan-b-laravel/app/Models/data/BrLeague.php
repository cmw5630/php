<?php

namespace App\Models\data;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class BrLeague extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeOptaAllLeagues($query)
  {
    return $query->withWhereHas('optaLeague', function ($_query) {
      return $_query->withoutGlobalScopes();
    });
  }

  public function optaLeague()
  {
    return $this->belongsTo(League::class, 'opta_league_id');
  }
}

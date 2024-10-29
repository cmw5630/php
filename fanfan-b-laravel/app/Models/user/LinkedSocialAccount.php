<?php

namespace App\Models\user;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class LinkedSocialAccount extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $fillable = [
    'provider_name',
    'provider_id',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}

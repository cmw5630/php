<?php

namespace App\Models\community;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Post extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'updated_at',
    'deleted_at',
  ];

  public function setContentAttribute($value = null)
  {
    $this->attributes['content'] = is_null($value) ? '' : $value;
  }

  protected function scopeMakeHide()
  {
    if ($this->status  === 'hide') {
      $this->attributes['title'] = null;
      $this->attributes['content'] = null;
    }
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function comments()
  {
    return $this->hasMany(Comment::class);
  }
}

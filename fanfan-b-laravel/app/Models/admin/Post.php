<?php

namespace App\Models\admin;

use App\Models\community\Comment;
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

  protected $casts = [
    'is_display' => 'boolean'
  ];

  public function setContentAttribute($value = null)
  {
    $this->attributes['content'] = is_null($value) ? '' : $value;
  }
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function admin()
  {
    return $this->belongsTo(Admin::class);
  }


  public function comments()
  {
    return $this->hasMany(Comment::class);
  }
}

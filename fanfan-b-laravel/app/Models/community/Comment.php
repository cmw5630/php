<?php

namespace App\Models\community;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Comment extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'attach_images' => 'array'
  ];

  public function setContentAttribute($value = null)
  {
    $this->attributes['content'] = is_null($value) ? '' : $value;
  }

  protected function scopeMakeHide()
  {
    if ($this->status  === 'hide' || $this->status === 'delete') {
      $this->attributes['title'] = null;
      $this->attributes['content'] = null;
    }
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function mentionedUser()
  {
    return $this->belongsTo(User::class, 'mentioned_user_id');
  }


  public function replies()
  {
    return $this->hasMany(self::class, 'parent_comment_id', 'id');
  }

  public function post()
  {
    return $this->belongsTo(Post::class);
  }

  public function parent()
  {
    return $this->belongsTo(self::class, 'parent_comment_id', 'id');
  }
}

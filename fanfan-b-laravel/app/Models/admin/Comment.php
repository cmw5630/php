<?php

namespace App\Models\admin;

use App\Models\community\Post;
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

  public function setContentAttribute($value = null)
  {
    $this->attributes['content'] = is_null($value) ? '' : $value;
  }
  protected function getContentAttribute()
  {
    if (!$this->original['is_display']) {
      return __('community.restricted', ['attribute' => 'comment', 'action' => 'restricted']);
    }
    if (!is_null($this->original['deleted_at'])) {
      return __('community.restricted', ['attribute' => 'comment', 'action' => 'deleted']);
    }
    return $this->attributes['content'];
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
}

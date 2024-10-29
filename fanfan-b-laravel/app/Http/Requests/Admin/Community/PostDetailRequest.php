<?php

namespace App\Http\Requests\Admin\Community;
use App\Http\Requests\Api\Community\PostDetailRequest as ApiPostDetail;
use App\Models\community\Post;
use Illuminate\Validation\Rule;
use Str;

class PostDetailRequest extends ApiPostDetail
{
  public function rules()
  {
    if (Str::lower($this->getMethod()) === 'get') {
      return [
        'post_id' => [
          'required',
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
        ]
      ];
    } else {
      return [
        'post_id' => [
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
            ->whereNotNull('admin_id')
        ]
      ];
    }
  }
}

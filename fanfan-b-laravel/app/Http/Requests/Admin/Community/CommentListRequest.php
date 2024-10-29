<?php

namespace App\Http\Requests\Admin\Community;
use App\Enums\CommunityStatus;
use App\Http\Requests\Api\Community\CommentListRequest as ApiCommentListRequest;
use App\Models\community\Comment;
use App\Models\community\Post;
use Illuminate\Validation\Rule;
use Str;

class CommentListRequest extends ApiCommentListRequest
{
  public function rules()
  {
    $rules = [
      //
    ];

    if (!Str::endsWith($this->route()->uri(), 'replies')) {
      // 일반모드
      $rules = [
        ...$rules,
        'post_id' => [
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
        ],
        'page' => ['integer', 'min:1'],
        'per_page' => $this->perPageRule(),
        'more_list' => ['nullable', 'json']
      ];
    } else {
      // 답글모드
      $rules = [
        ...$rules,
        'post_id' => [
          Rule::exists(Comment::getTableName(), 'post_id')->whereNull('deleted_at')
        ],
        'comment_id' => [
          'required_with:offset',
          Rule::exists(Comment::getTableName(), 'parent_comment_id')->whereNull('deleted_at')
        ],
        'offset' => [
          'required',
          'integer',
          Rule::exists(Comment::getTableName(), 'id')->whereNull('deleted_at')
        ],
      ];
    }

    return $rules;
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

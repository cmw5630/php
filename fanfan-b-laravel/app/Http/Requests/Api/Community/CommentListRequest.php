<?php

namespace App\Http\Requests\Api\Community;

use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\community\Comment;
use App\Models\community\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Str;

class CommentListRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['post_id'] = $this->route('post_id');
    if (!is_null($this->route('comment_id'))) {
      $request['comment_id'] = $this->route('comment_id');
    }

    return $request;
  }

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
            ->where('status', CommunityStatus::NORMAL)
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
            ->where('status', CommunityStatus::NORMAL)
        ],
        'comment_id' => [
          'required_with:offset',
          Rule::exists(Comment::getTableName(), 'parent_comment_id')->whereNull('deleted_at')
            ->where('status', CommunityStatus::NORMAL)
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

  protected function prepareForValidation(): void
  {
    if (!Str::endsWith($this->route()->uri(), 'replies')) {
      $addParamArray = [
        'page' => 1,
        'per_page' => 20,
        'more_list' => null
      ];
    } else {
      $addParamArray = [
        'offset' => null,
      ];
    }

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }
  }
}

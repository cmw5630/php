<?php

namespace App\Http\Requests\Api\Community;
use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\community\Comment;
use App\Models\community\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Str;

class CommentStoreRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    if (Str::lower($this->getMethod()) === 'post') {
      $request['post_id'] = $this->route('post_id');
    } else {
      $request['comment_id'] = $this->route('comment_id');
    }

    return $request;
  }

  public function rules()
  {
    $rules = [
      'content' => ['nullable', 'required_without_all:attach_images', 'string'],
      'attach_images' => ['nullable', 'required_without_all:content', 'array']
    ];

    if (Str::lower($this->getMethod()) === 'post') {
      $rules = [
        ...$rules,
        'post_id' => [
          'required',
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
            ->where('status', CommunityStatus::NORMAL)
        ],
        'parent_id' => [
          'required_with:mentioned_user_id',
          Rule::exists(Comment::getTableName(), 'id')->whereNull('deleted_at')
            ->where('post_id', $this->post_id)->whereNull('parent_comment_id'),
        ],
        'mentioned_user_id' => [
          Rule::exists(Comment::getTableName(), 'user_id')->where(function ($query) {
            $query->where('id', $this->parent_id)
              ->orWhere('parent_comment_id', $this->parent_id);
          })
        ],
      ];
    } else {
      $rules = [
        ...$rules,
        'comment_id' => [
          'required',
          Rule::exists(Comment::getTableName(), 'id')->whereNull('deleted_at')
            ->where('status', CommunityStatus::NORMAL)
            ->where('user_id', $this->user()->id),
        ],
        'mentioned_user_id' => [
          'size:0',
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
    $addParamArray = [
      'content' => null,
      'attach_images' => [],
    ];

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }
  }
}

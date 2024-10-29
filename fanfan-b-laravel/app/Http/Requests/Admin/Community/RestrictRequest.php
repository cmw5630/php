<?php

namespace App\Http\Requests\Admin\Community;
use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\admin\Comment;
use App\Models\Code;
use App\Models\community\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestrictRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['post_id'] = $this->route('post_id');

    return $request;
  }

  public function rules()
  {
    return [
      'type' => [
        'required',
        Rule::in([CommunityStatus::HIDE, CommunityStatus::DELETE])
      ],
      'post_id' => ['required',
        Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
          ->where('status', CommunityStatus::NORMAL)
      ],
      'comment_id' => ['nullable', Rule::exists(Comment::getTableName(), 'id')->whereNull('deleted_at')
        ->where('post_id', $this->post_id)
        ->whereNotnull('user_id')
      ],
      'reason' => ['required', $this->codeExists('R01')->whereIn('code', ['01', '02', '03'])],
    ];
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
      'comment_id' => null,
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

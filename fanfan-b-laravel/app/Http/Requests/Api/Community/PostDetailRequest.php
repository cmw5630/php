<?php

namespace App\Http\Requests\Api\Community;
use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\community\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Str;

class PostDetailRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['post_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    if (Str::lower($this->getMethod()) === 'get') {
      return [
        'post_id' => [
          'required',
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
            ->where('status', CommunityStatus::NORMAL)
        ]
      ];
    } else {
      return [
        'post_id' => [
          Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')
            ->where('status', CommunityStatus::NORMAL)
            ->where('user_id', $this->user()->id)
        ]
      ];
    }
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
      //
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

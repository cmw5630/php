<?php

namespace App\Http\Requests\Api\Community;
use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\community\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentDeleteRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['comment_id'] = $this->route('comment_id');

    return $request;
  }

  public function rules()
  {
    return [
      'comment_id' => [
        'required',
        Rule::exists(Comment::getTableName(), 'id')->whereNull('deleted_at')
          ->where('status', CommunityStatus::NORMAL)
          ->where('user_id', $this->user()->id),
      ],
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

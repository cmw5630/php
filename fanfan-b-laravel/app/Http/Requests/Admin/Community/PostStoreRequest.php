<?php

namespace App\Http\Requests\Admin\Community;

use App\Http\Requests\FormRequest;
use App\Models\community\Board;
use App\Models\community\BoardCategory;
use App\Models\community\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostStoreRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['post_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'post_id' => [
        'nullable',
        Rule::exists(Post::getTableName(), 'id')->whereNull('deleted_at')->whereNotnull('admin_id')
      ],
      // 'sort_type' => ['required', Rule::in(PostSortType::getValues())],
      'board_id' => ['required', 'exists:' . Board::getTableName() . ',id'],
      'category_id' => [
        'required',
        Rule::exists(BoardCategory::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('board_id', $this->board_id);
          })
      ],
      'title' => ['required', 'string', 'max:150'],
      'content' => ['nullable', 'string'],
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
      'post_id' => null,
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

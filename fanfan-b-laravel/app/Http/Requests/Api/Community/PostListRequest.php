<?php

namespace App\Http\Requests\Api\Community;

use App\Enums\CommunityStatus;
use App\Http\Requests\FormRequest;
use App\Models\community\Board;
use App\Models\community\BoardCategory;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class PostListRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['board_id'] = $this->route('board_id');

    return $request;
  }

  public function rules()
  {
    return [
      'board_id' => ['required', 'exists:' . Board::getTableName() . ',id'],
      'category_id' => [
        'nullable',
        Rule::exists(BoardCategory::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('board_id', $this->board_id);
          })
      ],
      'list_type' => ['nullable', Rule::in([CommunityStatus::NORMAL, CommunityStatus::DELETE])],
      // 'sort_type' => ['nullable', Rule::in(PostSortType::getValues())],
      'search_type' => ['in:title,content,name,title_content', 'required_with:q'],
      'q' => ['required_with:search_type'],
      'page' => ['integer', 'min:1'],
      'per_page' => $this->perPageRule(),
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
      'category_id' => null,
      'list_type' => CommunityStatus::NORMAL,
      // 'sort_type' => null,
      'q' => null,
      'per_page' => 20,
      'page' => 1,
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

<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;
use App\Models\data\Season;
use App\Models\game\Game;
use Illuminate\Http\Request;

class AllGameJoinListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => ['nullable', 'string', 'exists:' . Season::getTableName() . ',id'],
      'q' => ['nullable', 'string'],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule()
    ];
  }

  public function messages()
  {
    return [];
  }

  public function attributes()
  {
    return [];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'season' => '1jt5mxgn4q5r6mknmlqv5qjh0',
      'q' => null,
      'page' => 1,
      'per_page' => 20
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

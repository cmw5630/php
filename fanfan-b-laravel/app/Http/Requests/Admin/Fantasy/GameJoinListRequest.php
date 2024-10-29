<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;
use App\Models\game\Game;
use Illuminate\Http\Request;

class GameJoinListRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['game_id'] = $this->route('game_id');

    return $request;
  }

  public function rules()
  {
    return [
      'game_id' => [
        'required',
        'int',
        'exists:' . Game::getTableName() . ',id'
      ],
      'q' => [
        'nullable',
        'string',
      ],
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

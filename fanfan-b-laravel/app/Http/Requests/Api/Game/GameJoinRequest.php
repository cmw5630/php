<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\game\Game;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameJoinRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['game_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'game_id' => ['required', 'int', 'exists:' . Game::getTableName() . ',id'],
      'lineup' => ['required', 'json'],
      'formation' => ['required', 'string', Rule::in(config('constant.LINEUP_FORMATION'))],
      'mode' => ['nullable', 'boolean']
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
}

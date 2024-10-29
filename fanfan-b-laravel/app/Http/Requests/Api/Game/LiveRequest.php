<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\game\Game;
use App\Models\game\GameJoin;

class LiveRequest extends FormRequest
{
  public function rules()
  {
    if (strpos($this->path(), 'rank')) {
      $model = Game::getTableName();
      $type = 'int';
    } else if (strpos($this->path(), 'user_lineup')) {
      $model = GameJoin::getTableName();
      $type = 'int';
    } else {
      $model = Schedule::getTableName();
      $type = 'string';
    }

    return [
      'id' => [
        'required',
        $type,
        'exists:' . $model . ',id'
      ],
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

<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\game\Game;
use App\Models\game\QuestType;
use Illuminate\Http\Request;

class StadiumRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    if (!isset($request['id'])) {
      $request['id'] = $this->route('id');
    }

    return $request;
  }

  public function rules()
  {
    // game 분기처리는 가장 나중에 쓸 것.
    $pathName = $this->path();
    if (strpos($pathName, 'seasons')) {
      $model = League::getTableName();
      $type = 'string';
    } else if (strpos($pathName, 'quest')) {
      $model = QuestType::getTableName();
      $type = 'integer';
    } else if (strpos($pathName, 'top3') || strpos($pathName, 'detail')) {
      $model = Game::getTableName();
      $type = 'integer';
    } else if (strpos($pathName, 'main')) {
      $model = Season::getTableName();
      $type = 'string';
    } else if (strpos($pathName, 'lineup') || strpos($pathName, 'game')) {
      $model = Game::getTableName();
      $type = 'integer';
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

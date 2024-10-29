<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\data\Team;


class LiveSideRequest extends FormRequest
{
  public function rules()
  {
    return [
      'id' => [
        'required',
        'string',
        'exists:' . Schedule::getTableName() . ',id'
      ],
      'team_id' => [
        'required',
        'string',
        'exists:' . Team::getTableName() . ',id'
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

<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;
use App\Models\data\Season;

class PossibleScheduleRequest extends FormRequest
{
  public function rules()
  {
    $return = [
      'season' => ['required', 'string', 'exists:' . Season::getTableName() . ',id'],
    ];

    if ($this->route()->methods[0] === 'GET') {
      $return = [
        ...$return,
        'round' => ['required', 'int', 'min:1']
      ];
    } else {
      $return = [
        ...$return,
        'schedules' => ['required', 'json']
      ];
    }

    return $return;
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

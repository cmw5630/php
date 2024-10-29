<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;

class MomentumRequest extends FormRequest
{
  public function rules()
  {
    return [
      'schedule_id' => [
        'required',
        'exists:' . Schedule::getTableName() . ',id'
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

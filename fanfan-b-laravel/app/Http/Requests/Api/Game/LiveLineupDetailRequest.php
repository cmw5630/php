<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\game\PlateCard;

class LiveLineupDetailRequest extends FormRequest
{
  public function rules()
  {
    return [
      'schedule_id' => [
        'required',
        'exists:' . Schedule::getTableName() . ',id'
      ],
      'player_id' => [
        'required',
        'exists:' . PlateCard::getTableName() . ',player_id'
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

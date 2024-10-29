<?php

namespace App\Http\Requests\Api\Stat;

use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;

class TeamVoteRequest extends FormRequest
{
  public function rules()
  {
    return [
      'schedule_id' => ['required', 'string', 'exists:' . Schedule::getTableName() . ',id'],
      'vote' => ['required', 'string', 'in:home,away'],
      'count' => ['required', 'int', 'min:1'],
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

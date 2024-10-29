<?php

namespace App\Http\Requests\Api\Stat;

use App\Http\Requests\FormRequest;
use App\Models\data\Team;
use Illuminate\Http\Request;

class TeamDetailRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['team_id'] = $this->route('team_id');

    return $request;
  }

  public function rules()
  {
    return [
      'team_id' => ['required', 'string', 'exists:' . Team::getTableName() . ',id']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

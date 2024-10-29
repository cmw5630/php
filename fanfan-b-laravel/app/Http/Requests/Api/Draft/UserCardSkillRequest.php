<?php

namespace App\Http\Requests\Api\Draft;

use App\Http\Requests\FormRequest;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserCardSkillRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_plate_card_id'] = $this->route('user_plate_card_id');

    return $request;
  }

  public function rules()
  {
    return [
      'user_plate_card_id' => [
        Rule::exists(UserPlateCard::getTableName(), 'id')
      ]
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

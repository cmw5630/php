<?php

namespace App\Http\Requests\Api\Market;
use App\Http\Requests\FormRequest;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;

class UserPlateCardIdRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_plate_card_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'user_plate_card_id' => ['exists:' . UserPlateCard::getTableName() . ',id']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

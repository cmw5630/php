<?php

namespace App\Http\Requests\Api\Draft;

use App\Http\Requests\FormRequest;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserCardBurnRequest extends FormRequest
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
      'user_plate_card_id' => [
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where('user_id', $this->user()->id)
          ->where('is_free', false)
          ->whereNull('deleted_at'),
      ]
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      //
    ];

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }
  }
}

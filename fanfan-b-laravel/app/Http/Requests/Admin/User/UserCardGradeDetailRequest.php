<?php

namespace App\Http\Requests\Admin\User;

use App\Http\Requests\FormRequest;
use App\Models\user\User;
use App\Models\user\UserPlateCard;
use Illuminate\Validation\Rule;

class UserCardGradeDetailRequest extends FormRequest
{
  public function rules()
  {
    return [
      'user_plate_card_id' => [
        'required',
        'exists:'.UserPlateCard::getTableName().',id',
      ],
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
      // 'q' => null,
      // 'search_type' => null,
      // 'page' => 1
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

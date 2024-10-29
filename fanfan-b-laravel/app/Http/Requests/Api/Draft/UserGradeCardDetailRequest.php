<?php

namespace App\Http\Requests\Api\Draft;

use App\Http\Requests\FormRequest;
use App\Models\user\UserPlateCard;

class UserGradeCardDetailRequest extends FormRequest
{
  public function rules()
  {
    return [
      'user_plate_card' => [
        'required',
        'int',
        'exists:' . UserPlateCard::getTableName() . ',id',
      ],
      'mode' => [
        'nullable',
        'in:ingame',
      ]
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function prepareForValidation(): void
  {
    $addParamArray = [
      'mode' => null,
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

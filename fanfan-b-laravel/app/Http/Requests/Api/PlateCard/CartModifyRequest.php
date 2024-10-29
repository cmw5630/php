<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Http\Requests\FormRequest;

class CartModifyRequest extends FormRequest
{
  public function rules()
  {
    return [
      'value' => ['required', 'int', 'in:-1,1'],
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

<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Http\Requests\FormRequest;
use App\Models\game\PlateCard;

class CartAddRequest extends FormRequest
{
  public function rules()
  {
    return [
      'plate_card_id' => ['required', 'int', 'exists:' . PlateCard::getTableName() . ',id'],
      'quantity' => ['required', 'int', 'min:1', 'max:20']
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

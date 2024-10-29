<?php

namespace App\Http\Requests\Admin\User;

use App\Http\Requests\FormRequest;
use App\Models\user\User;

class UserCardsRequest extends FormRequest
{
  public function rules()
  {
    return [
      'user_id' => ['required', 'exists:' . User::getTableName() . ',id'],
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

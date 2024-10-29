<?php

namespace App\Http\Requests\Api\Auth;
use App\Http\Requests\FormRequest;

class AuthCheckRequest extends FormRequest
{
  public function rules()
  {
    return [
      'email' => 'email|required',
      'password' => 'required'
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

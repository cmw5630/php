<?php

namespace App\Http\Requests\Admin\Auth;
use App\Http\Requests\FormRequest;

class AuthLoginRequest extends FormRequest
{
  public function rules()
  {
    return [
      'login_id' => [
        'required',
      ],
      'password' => [
        'required',
      ],

    ];
  }

  public function messages()
  {
    return [
      'login_id.required' => '아이디는 필수입니다.',
      'password.required' => '비밀번호는 필수입니다.',
    ];
  }
}

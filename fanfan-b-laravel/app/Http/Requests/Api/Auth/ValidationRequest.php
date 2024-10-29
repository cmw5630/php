<?php

namespace App\Http\Requests\Api\Auth;
use App\Http\Requests\FormRequest;

class ValidationRequest extends FormRequest
{
  public function rules()
  {
    return [
      'key' => ['required', 'string'],
      'q' => ['nullable', 'string']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

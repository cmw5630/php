<?php

namespace App\Http\Requests\Api\Auth;
use App\Http\Requests\FormRequest;

class GoogleAuthCodeRequest extends FormRequest
{
  public function rules()
  {
    return [
      'code' => ['required'],
      'redirect' => ['nullable']
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
      'code' => null,
      'redirect' => null,
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

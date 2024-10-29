<?php

namespace App\Http\Requests\Api\Auth;
use App\Http\Requests\FormRequest;

class SocialAuthCodeRequest extends FormRequest
{
  public function rules()
  {
    $provider = $this->route('provider');

    $rules = [
      'code' => ['required'],
    ];

    if ($provider === 'google') {
      $rules['redirect'] = ['nullable'];
    } else {
      $rules['state'] = ['required'];
    }

    return $rules;
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

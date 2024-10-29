<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;

class UserSearchRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['required']
    ];
  }

  public function attributes()
  {
    return [
      'q' => 'keyword'
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

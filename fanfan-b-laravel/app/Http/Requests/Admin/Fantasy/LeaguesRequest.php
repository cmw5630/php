<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;

class LeaguesRequest extends FormRequest
{
  public function rules()
  {
    return [
      'lists' => ['required', 'json'],
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

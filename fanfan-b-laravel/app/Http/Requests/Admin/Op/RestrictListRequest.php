<?php

namespace App\Http\Requests\Admin\Op;
use App\Http\Requests\FormRequest;

class RestrictListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['nullable'],
      'page' => ['integer', 'min:1'],
      'per_page' => $this->perPageRule()
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
      'q' => null,
      'page' => 1,
      'per_page' => 20,
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

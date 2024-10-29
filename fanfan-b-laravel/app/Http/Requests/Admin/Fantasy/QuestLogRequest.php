<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;

class QuestLogRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['nullable', 'string'],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule()
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

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'q' => null,
      'page' => 1,
      'per_page' => 20
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

<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class LoginHistoryRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['nullable', 'string'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date'],
      'page' => ['integer', 'min:1'],
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
      'start_date' => now()->toDateString(),
      'end_date' => now()->toDateString(),
      'page' => 1
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

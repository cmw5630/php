<?php

namespace App\Http\Requests\Api\Home;
use App\Http\Requests\FormRequest;

class BestLineupRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => ['required', 'string'],
      'round' => ['required', 'integer'],
      'schedule' => ['nullable']
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
      'schedule' => null,
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

<?php

namespace App\Http\Requests\Api\Simulation;
use App\Http\Requests\FormRequest;

class UploadRefSequenceRequest extends FormRequest
{
  public function rules()
  {
    return [
      'file' => ['mimes:xlsx']
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

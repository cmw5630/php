<?php

namespace App\Http\Requests\Api\Community;
use App\Http\Requests\FormRequest;

class ImageUploadRequest extends FormRequest
{
  public function rules()
  {
    return [
      'image' => ['image', 'max:5120'],
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

<?php

namespace App\Http\Requests\Admin\Fantasy;
use App\Http\Requests\FormRequest;

class PredictVoteListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule(),
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

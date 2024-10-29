<?php

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;
use App\Models\data\Season;

class SeasonRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => [
        'nullable',
        'string',
        'exists:' . Season::class . ',id',
      ],
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
      'season' => null
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

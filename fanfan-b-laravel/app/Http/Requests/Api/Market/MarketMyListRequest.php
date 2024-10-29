<?php

namespace App\Http\Requests\Api\Market;
use App\Http\Requests\FormRequest;

class MarketMyListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'type' => ['nullable', 'in:sell,buy'],
      'month' => ['nullable', 'date:Y-m'],
      'per_page' => $this->perPageRule(),
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  protected function prepareForValidation()
  {
    $addParamArray = [
      'type' => 'sell',
      'month' => now()->format('Y-m'),
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

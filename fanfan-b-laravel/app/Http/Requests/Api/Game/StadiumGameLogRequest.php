<?php

namespace App\Http\Requests\Api\Game;

use App\Enums\FantasyCalculator\OrderType;
use App\Http\Requests\FormRequest;
use App\Models\data\League;

class StadiumGameLogRequest extends FormRequest
{
  public function rules()
  {
    return [
      'league' => [
        'nullable',
        'array',
        'exists:' . League::getTableName() . ',id'
      ],
      'active' => ['nullable', 'string'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date'],
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
      'league' => null,
      'type' => null,
      'start_date' => now()->subDays(60)->toDateString(),
      'end_date' => now()->toDateString(),
      'page' => 1,
      'per_page' => 10,
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

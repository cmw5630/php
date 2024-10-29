<?php

namespace App\Http\Requests\Api\Simulation;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationSeason;

class SimulationRankRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => ['nullable', 'string','exists:' . SimulationSeason::getTableName() . ',id'],
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
     'season' => null,
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

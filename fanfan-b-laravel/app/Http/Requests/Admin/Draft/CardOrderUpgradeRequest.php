<?php

namespace App\Http\Requests\Admin\Draft;

use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Team;

class CardOrderUpgradeRequest extends FormRequest
{
  public function rules()
  {
    return [
      'league' => ['nullable', 'string', 'exists:' . League::getTableName() . ',id'],
      'team' => ['nullable', 'string', 'exists:' . Team::getTableName() . ',id'],
      'q' => ['nullable', 'string'],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule(),
      'status' => ['nullable', 'string', 'in:complete,upgrading,plate']
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
      'league' => null,
      'team' => null,
      'q' => null,
      'page' => 1,
      'per_page' => 20,
      'status' => null
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

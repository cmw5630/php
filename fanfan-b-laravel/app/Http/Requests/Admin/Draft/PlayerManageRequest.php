<?php

namespace App\Http\Requests\Admin\Draft;

use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\game\PlateCard;
use Illuminate\Validation\Rule;

class PlayerManageRequest extends FormRequest
{
  public function rules()
  {
    return [
      'league' => ['required', 'string'],
      'club' => [
        'nullable',
        'array',
        Rule::exists(PlateCard::getTableName(), 'team_id')
          ->where(function ($query) {
            $query->where('league_id', $this->league);
          })
      ],
      'active' => ['nullable', 'string', Rule::in(array_merge(YesNo::getValues(), ['non_salary']))],
      'player_name' => ['nullable', 'string'],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule(),
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
      'league' => League::defaultLeague()->id,
      'club' => null,
      'active' => null,
      'player_name' => null,
      'page' => 1,
      'per_page' => 20,
      'position' => null,
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

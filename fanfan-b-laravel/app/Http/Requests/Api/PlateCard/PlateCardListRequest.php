<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Enums\FantasyCalculator\OrderType;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\game\PlateCard;
use Illuminate\Validation\Rule;

class PlateCardListRequest extends FormRequest
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
      'position' => ['nullable', 'array'],
      'player_name' => ['nullable', 'string'],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule(),
      'sort' => ['nullable', 'string', 'in:name,price,order_overall'],
      'order' => ['nullable', 'string', Rule::in(OrderType::getValues())]
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
      'position' => null,
      'player_name' => null,
      'other' => false,
      'page' => 1,
      'per_page' => 20,
      'sort' => 'name',
      'order' => OrderType::ASC
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

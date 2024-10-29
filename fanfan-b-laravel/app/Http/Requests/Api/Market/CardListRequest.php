<?php

namespace App\Http\Requests\Api\Market;

use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\FantasyCalculator\OrderType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Team;
use Illuminate\Validation\Rule;

class CardListRequest extends FormRequest
{
  public function rules()
  {
    $levels = FantasyDraftCategoryType::getValues();
    array_walk($levels, function ($val, $key) use (&$levels) {
      if ($val === 'summary') {
        unset($levels[$key]);
      } else {
        $levels[$key] .= '_level';
      }
    });
    $levels = [...$levels, 'draft_level', 'overall', 'min_price'];

    return [
      'league' => ['nullable', 'exists:' . League::getTableName() . ',id'],
      'club' => ['nullable', 'array'],
      'club.*' => ['exists:' . Team::getTableName() . ',id'],
      'sort' => ['nullable', Rule::in(array_values($levels))],
      'position' => ['nullable', 'array'],
      'position.*' => [Rule::in(PlayerPosition::getValues())],
      'grade' => ['nullable', 'array'],
      'grade.*' => ['nullable', Rule::in(CardGrade::getGrades())],
      'per_page' => $this->perPageRule(),
      'q' => ['nullable', 'string'],
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function attributes()
  {
    return [
      'club.*' => 'club',
      'grade.*' => 'grade',
      'position.*' => 'position',
    ];
  }

  protected function prepareForValidation()
  {
    $addParamArray = [
      'league' => League::defaultLeague()->id,
      'club' => null,
      'position' => null,
      'grade' => null,
      'page' => 1,
      'sort' => 'draft_level',
      'order' => OrderType::ASC,
      'per_page' => 20,
      'q' => null,
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

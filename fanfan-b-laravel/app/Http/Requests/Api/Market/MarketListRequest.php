<?php

namespace App\Http\Requests\Api\Market;
use App\Enums\AuctionType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\FantasyCalculator\OrderType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Team;
use App\Models\game\PlateCard;
use Illuminate\Validation\Rule;

class MarketListRequest extends FormRequest
{
  public function rules()
  {
    // 'in:draft_level,attacking_level,goalkeeping_level,passing_level,defensive_level,duel_level'
    $levels = FantasyDraftCategoryType::getValues();

    array_walk($levels, function ($val, $key) use (&$levels) {
      if ($val === 'summary') {
        unset($levels[$key]);
      } else {
        $levels[$key] .= '_level';
      }
    });
    $levels = [...$levels, 'draft_level', 'buynow_price', 'expired_at', 'overall'];

    return [
      'type' => ['nullable', Rule::in(AuctionType::getValues())],
      'league' => ['nullable', 'exists:' . League::getTableName() . ',id'],
      'club' => ['nullable', 'array'],
      'club.*' => ['exists:' . Team::getTableName() . ',id'],
      'sort' => ['nullable', Rule::in(array_values($levels))],
      'position' => ['nullable', 'array'],
      'position.*' => [Rule::in(PlayerPosition::getValues())],
      'grade' => ['nullable', 'array'],
      'grade.*' => ['nullable', Rule::in(CardGrade::getGrades())],
      'player_id' => ['nullable', 'exists:' . PlateCard::getTableName() . ',player_id'],
      'per_page' => $this->perPageRule(),
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
      'type' => null,
      'league' => League::defaultLeague()->id,
      'club' => null,
      'position' => null,
      'grade' => null,
      'q' => null,
      'player_id' => null,
      'page' => 1,
      'sort' => 'expired_at',
      'order' => OrderType::ASC,
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

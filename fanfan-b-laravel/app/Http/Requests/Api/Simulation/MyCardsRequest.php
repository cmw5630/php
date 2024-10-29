<?php

namespace App\Http\Requests\Api\Simulation;

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerSubPosition;
use App\Http\Requests\FormRequest;
use App\Models\data\SeasonTeam;
use Illuminate\Validation\Rule;

class MyCardsRequest extends FormRequest
{
  public function rules()
  {
    return [
      'player_name' => ['nullable', 'string'],
      'club' => [
        'nullable',
        'array',
        Rule::exists(SeasonTeam::class, 'team_id')->where(function ($query) {
          return (new SeasonTeam)->whereHas('season.league', function ($leagueQuery) {
            $leagueQuery->where('league_code', config('constant.DEFAULT_LEAGUE'));
          });
        })
      ],
      'grade' => [
        'nullable',
        'array',
        Rule::in(CardGrade::getValues())
      ],
      'position_type' => [
        'required',
        'in:position,sub_position'
      ],
      'position_value' => [
        'required',
        Rule::in(array_merge(PlayerPosition::getAllPositions(), PlayerSubPosition::getValues()))
      ],
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
      'player_name' => null,
      'page' => 1,
      'club' => null,
      'grade' => null,
      'per_page' => 20
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

<?php

namespace App\Http\Requests\Api\Game;

use App\Enums\Nations;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Season;
use Illuminate\Validation\Rule;

class StadiumRankingRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => [
        'required',
        'string',
        'exists:' . Season::getTableName() . ',id'
      ],
      'q' => [
        'nullable',
        'string'
      ],
      'page' => ['int', 'min:1'],
      'per_page' => $this->perPageRule()
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
      'season' => Season::currentSeasons()->where('league_id', League::defaultLeague()->id)->value('id'),
      'q' => null,
      'page' => 1,
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

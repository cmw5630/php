<?php

namespace App\Http\Requests\Api\Stat;

use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\StatCategory;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\game\Player;

class StatListRequest extends FormRequest
{
  public function rules()
  {
    $positionRules = config('constant.validation.rules.position');

    return [
      'position' => ['nullable', ...$positionRules],
      'team' => ['nullable', 'array'],
      'team.*' => ['exists:' . Team::getTableName() . ',id'],
      'mode' => ['nullable', 'in:total,per'],
      'player_id' => ['nullable', 'exists:' . Player::getTableName() . ',id'],
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
      'type' => 'team',
      'league' => League::defaultLeague()->id,
      'category' => StatCategory::SUMMARY,
      'position' => null,
      'team' => null,
      'player_id' => null,
      'mode' => 'total',
      'order' => 'desc',
      'page' => 1,
      'q' => null,
    ];

    $seasons = Season::getBeforeFuture([SeasonWhenType::BEFORE], $this->league ?? $addParamArray['league'])[$this->league ?? $addParamArray['league']];
    $addParamArray['season'] = $seasons['current']['id'];

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }

    if (!isset($this->sort)) {
      $this->merge([
        'sort' => config('stats.categories')[$this->type . ($this->mode === 'per' ? '_' . $this->mode : '')][$this->category][0]
      ]);
    }
  }
}

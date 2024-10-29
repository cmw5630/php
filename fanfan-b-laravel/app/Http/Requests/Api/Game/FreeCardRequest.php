<?php

namespace App\Http\Requests\Api\Game;

use App\Enums\FreeGame\FreeGameShuffleType;
use App\Enums\GameType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Requests\FormRequest;
use App\Models\game\Game;
use Illuminate\Validation\Rule;

class FreeCardRequest extends FormRequest
{
  public function rules()
  {
    return [
      'game_id' => [
        'required',
        'integer',
        Rule::exists(Game::getTableName(), 'id')
          ->whereIn('mode', [GameType::FREE, GameType::SPONSOR]),
      ],
      'position' => [
        'nullable',
        Rule::in(PlayerPosition::getValues()),
      ],
      'formation_place' => [
        'nullable',
        Rule::in(config(sprintf('constant.FORMATION_PLACE_MAP.442.%s', $this->position))),
      ],
      'shuffle_mode' => [
        'nullable',
        Rule::in(FreeGameShuffleType::getValues()),
      ]
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
      'position' => null,
      'formation_place' => null,
      'shuffle_type' => FreeGameShuffleType::COUNT,
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

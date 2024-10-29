<?php

namespace App\Http\Requests\Api\Game;

use App\Enums\GameType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Requests\FormRequest;
use App\Models\game\FreeCardShuffleMemory;
use App\Models\game\Game;
use Illuminate\Validation\Rule;

class UpdateLineupRequest extends FormRequest
{
  public function rules()
  {
    return [
      'game_id' => [
        'required',
        Rule::exists(Game::getTableName(), 'id')
          ->whereIn('mode', [GameType::FREE, GameType::SPONSOR])
      ],
      'position' => [
        'required',
        Rule::in(PlayerPosition::getValues())
      ],
      'shuffle_id' => [
        'required',
        Rule::exists(FreeCardShuffleMemory::getTableName(), 'id')
          ->where('is_open', true)
      ],
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
}

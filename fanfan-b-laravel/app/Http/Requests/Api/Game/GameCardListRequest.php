<?php

namespace App\Http\Requests\Api\Game;

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\data\Team;
use App\Models\game\Game;
use App\Models\user\UserPlateCard;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class GameCardListRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['game_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'game_id' => [
        'required',
        'int',
        'exists:' . Game::getTableName() . ',id'
      ],
      'club' => [
        'nullable',
        'array',
        'exists:' . Team::getTableName() . ',id'
      ],
      'grade' => [
        'nullable',
        'array',
        Rule::in(CardGrade::getValues())
      ],
      'position' => [
        'nullable',
        'string',
        Rule::in(PlayerPosition::getValues())
      ],
      'player_name' => [
        'nullable',
        'string'
      ],
      'user_plate_card_id' => [
        'nullable',
        'integer',
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where('user_id', $this->user()->id),
      ],
      'page' => ['integer', 'min:1'],
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
    $clubs = $this->club;
    if (!isset($this->club)) {
      // 게임ID로 해당되는 schedule들에서 team 뽑기

      $clubs = Schedule::withUnrealSchedule()
        ->whereHas('gamePossibleSchedule.gameSchedule.game', function ($query) {
          $query->where('id', $this->game_id);
        })->select('home_team_id', 'away_team_id')
        ->get()
        ->flatMap(function ($info) {
          return [$info->home_team_id, $info->away_team_id];
        })->toArray();
    }


    $position = $this->position;
    if (!isset($this->position)) {
      $position = PlayerPosition::ATTACKER;
    }

    $addParamArray = [
      'club' => $clubs,
      'grade' => [],
      'position' => $position,
      'player_name' => null,
      'user_plate_card_id' => null,
      'page' => 1,
      'per_page' => 15,
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

<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Enums\GameType;
use App\Http\Requests\FormRequest;
use App\Models\data\Season;
use App\Models\game\Game;
use App\Models\user\User;
use Illuminate\Validation\Rule;

class GameMakeRequest extends FormRequest
{
  public function rules()
  {
    return [
      'game_id' => ['nullable', 'int', 'exists:' . Game::getTableName() . ',id'],
      'season' => ['required', 'string', 'exists:' . Season::getTableName() . ',id'],
      'mode' => ['nullable', 'string', Rule::in(GameType::getValues())],
      'schedules' => ['required', 'json'],
      'rewards' => ['required', 'int'],
      'prize_rate' => ['required', 'int', 'min:0', 'max:100'],
      'banner' => ['nullable', 'mimes:jpeg,png', 'max:3096'],
      'is_popular' => ['nullable', 'string', 'in:0,1'],
      'reservation_time' => ['nullable', 'date_format:Y-m-d H:i'],
      'user_id' => ['nullable', 'int', 'exists:' . User::getTableName() . ',id'],
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
      'game_id' => null,
      'is_popular' => true,
      'mode' => 'normal',
      'reservation_time' => null,
      'banner' => null,
      'user_id' => null,
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

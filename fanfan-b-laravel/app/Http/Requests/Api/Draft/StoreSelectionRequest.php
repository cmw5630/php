<?php

namespace App\Http\Requests\Api\Draft;

use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Http\Requests\FormRequest;
use App\Models\game\GamePossibleSchedule;
use App\Models\user\UserPlateCard;
use Illuminate\Validation\Rule;

class StoreSelectionRequest extends FormRequest
{
  protected $teamId;
  protected $validTeams = [];

  public function rules()
  {
    $input = $this->all();
    $input['cost'] = (int)$input['cost'];
    $input['level'] = (int)$input['level'];
    $input['totalPrice'] = (int)$input['totalPrice'];
    $this->replace($input);

    return [
      'schedule_id' => [
        'required',
        'string',
        Rule::exists(GamePossibleSchedule::getTableName(), 'schedule_id')
          ->where(function ($query) {
            $query->where('status', ScheduleStatus::FIXTURE);
          })
      ],
      'user_plate_card_id' => [
        'required',
        'string',
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('user_id', $this->user()->id)
              ->where('status', PlateCardStatus::PLATE);
          })
      ],
      'position' => [
        'required',
        Rule::in(PlayerPosition::getValues())
      ],
      'cost' => [
        'required',
        'numeric',
      ],
      'level' => [
        'required',
        'numeric',
      ],
      'totalPrice' => [
        'required',
        'numeric',
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

  public function prepareForValidation(): void
  {
    $addParamArray = [
      'selections' => [],
      'cost' => 0,
      'level' => 0,
      'totalPrice' => 0,
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

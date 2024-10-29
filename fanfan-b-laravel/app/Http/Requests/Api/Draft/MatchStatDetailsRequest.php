<?php

namespace App\Http\Requests\Api\Draft;

use App\Enums\Opta\Card\DraftCardStatus;
use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchStatDetailsRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['player_id'] = $this->route('player_id');

    return $request;
  }

  public function rules()
  {
    return [
      'player_id' => [
        'required',
        'string',
        'exists:' . PlateCard::getTableName() . ',player_id',
      ],
      'season_id' => [
        'nullable',
        'string',
        'exists:' . Season::getTableName() . ',id',
      ],
      'limit' => [
        'required',
        'int',
      ],
      'schedule_id' => [
        'nullable',
        'exists:' . Schedule::getTableName() . ',id',
      ],
      'end_date' => [
        'nullable',
      ],
      'user_plate_card' => [
        'nullable',
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where(function ($query) {
            $query->whereIn('status', [DraftCardStatus::COMPLETE, DraftCardStatus::UPGRADING]);
          })
      ]
    ];
  }

  public function messages()
  {
    return [];
  }

  protected function prepareForValidation(): void
  {
    $season_id = $this->season_id;
    if (!isset($this->season_id)) {
      $season_id = PlateCard::where('player_id', $this->player_id)->withTrashed()->value('season_id');
    }

    if (!isset($this->schedule_id)) {
      $endDate = now();
    } else {
      $endDate = Schedule::where('id', $this->schedule_id)
        ->value('started_at');
    }

    $addParamArray = [
      'season_id' => $season_id,
      'end_date' => $endDate,
      'user_plate_card' => null,
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

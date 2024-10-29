<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\data\Season;
use Illuminate\Validation\Rule;

class UserCardHistoryRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => [
        'required',
        'string',
        'exists:' . Season::getTableName() . ',id'
      ],
      'round' => ['nullable', 'int'],
      'schedule' => [
        'nullable',
        Rule::requiredIf(!is_null($this->end_id)),
        'string',
        'exists:' . Schedule::getTableName() . ',id'
      ],
      'limit' => ['required', 'int'],
      'end_id' => [
        'nullable',
        Rule::requiredIf(!is_null($this->schedule)),
        'int'
      ],
      'index' => [
        'nullable',
        'int',
      ],
      'status' => [
        'nullable',
        Rule::requiredIf(!is_null($this->schedule)),
        'string',
        Rule::in(ScheduleStatus::getValues()),
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
    $lastRound = $this->round;
    if (!isset($this->round)) {
      $lastRound = Schedule::where('season_id', $this->season)
        ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])->orderByDesc('round')->value('round');
    }

    $addParamArray = [
      'round' => $lastRound,
      'schedule' => null,
      'limit' => 8,
      'end_id' => null,
      'index' => null,
      'status' => null,
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

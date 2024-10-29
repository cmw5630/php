<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Enums\FantasyCalculator\OrderType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\data\League;
use App\Models\data\Season;
use Illuminate\Validation\Rule;

class ScheduleListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => ['required', 'string', 'exists:' . Season::getTableName() . ',id'],
      'status' => ['nullable', 'string', Rule::in(ScheduleStatus::getValues())],
      'sort' => ['nullable', 'string', 'in:round'],
      'order' => ['nullable', 'string', Rule::in(OrderType::getValues())],
      'round' => ['nullable', 'int']
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
      'status' => null,
      'season' => Season::where([['league_id', League::defaultLeague()->id], ['active', YesNo::YES]])->value('id'),
      'sort' => 'round',
      'order' => OrderType::DESC,
      'round' => null
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

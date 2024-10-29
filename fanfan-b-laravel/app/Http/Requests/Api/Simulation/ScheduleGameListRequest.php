<?php

namespace App\Http\Requests\Api\Simulation;

use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationSeason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleGameListRequest extends FormRequest
{

  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_id'] = $this->user()->id;

    return $request;
  }

  public function rules()
  {
    // TODO : 확인 안한 경기 있으면 현재 시즌만 접근 되도록 처리?
    $weekdays = Carbon::getDays();
    array_shift($weekdays);
    return [
      'user_id' => [
        'required',
        Rule::exists(SimulationApplicant::getTableName(), 'user_id')->where('active', YesNo::YES)
      ],
      'year' => [
        'required',
        'int',
        'digits:4',
        function ($key, $value, $fail) {
          if (!SimulationSeason::whereYear('first_started_at', $value)->exists()) {
            $fail(__('The selected ' . $key . ' is invalid.'));
          }
        },
      ],
      'season' => ['nullable', 'string', Rule::exists(SimulationSeason::getTableName(), 'id')],
      'weekday' => ['nullable', 'string', Rule::in($weekdays)],
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
    $applicant = SimulationApplicant::where('user_id', $this->user()->id)
      ->first();

    $currentSeason = SimulationSeason::where('server', $applicant->server)
      ->currentSeasons()
      ->first();

    $timezone = config('simulationpolicies.server')[$applicant->server]['timezone'];
    $day = now($timezone);
    if ($day->isSunday()) {
      if ($day->hour >= 10) {
        $day->addDay();
      } else {
        $day->subDay();
      }
    }

    $addParamArray = [
      'year' => $currentSeason->first_started_at->year,
      'season' => $currentSeason->id,
      'weekday' => $day->englishDayOfWeek,
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

<?php

namespace App\Http\Requests\Api\Simulation;

use App\Enums\Opta\YesNo;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationSchedule;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class SimulationScheduleRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_id'] = $this->user()->id;
    $request['schedule_id'] = $this->route('schedule_id');
    return $request;
  }

  public function rules()
  {
    return [
      'schedule_id' => [
        'required',
        'string',
        function ($key, $value, $fail) {
          if (strpos($this->path(), 'lineup')) {
            if (!$this->rulesForScheduleId($value) && !$this->rulesForFixtureScheduleId($value)) {
              $fail(__('The selected ' . $key . ' is invalid.'));
            }
          } else if (!$this->rulesForScheduleId($value)) {
            $fail(__('The selected ' . $key . ' is invalid.'));
          }
        },
      ],
      'user_id' => [
        'required',
        Rule::exists(SimulationApplicant::getTableName(), 'user_id')->where('active', YesNo::YES)
      ]
    ];
  }

  private function rulesForScheduleId($value)
  {
    // lineup, commentary, resultCheck
    // lineup => both
    // else => scheduleId
    return SimulationLineupMeta::whereHas('applicant', function ($query) {
      $query->where('user_id', $this->user_id);
    })
      ->where('schedule_id', $value)
      ->when(!strpos($this->path(), 'lineup'), function ($query) {
        $query->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where('status', SimulationScheduleStatus::PLAYED);
        });
      })->exists();
  }

  private function rulesForFixtureScheduleId($value)
  {
    return SimulationSchedule::where(function ($query) {
      $query->whereHas('home', function ($home) {
        $home->where('user_id', $this->user_id);
      })->orWhereHas('away', function ($away) {
        $away->where('user_id', $this->user_id);
      });
    })->where([
      ['status', SimulationScheduleStatus::FIXTURE],
      ['id', $value]
    ])->whereRaw("NOW() BETWEEN DATE_SUB(started_at, INTERVAL 10 MINUTE) AND started_at")
      ->exists();
  }

  public function messages()
  {
    return [
      //
    ];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [];

    foreach ($addParamArray as $key => $value) {
      if (!$this->has($key)) {
        $this->merge([
          $key => $value
        ]);
      }
    }
  }
}

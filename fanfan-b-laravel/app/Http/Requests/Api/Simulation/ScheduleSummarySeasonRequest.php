<?php


namespace App\Http\Requests\Api\Simulation;

use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationSeason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleSummarySeasonRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_id'] = $this->user()->id;

    return $request;
  }
  public function rules()
  {
    return [
      'user_id' => [
        'required',
        Rule::exists(SimulationApplicant::getTableName(), 'user_id')->where('active', YesNo::YES)
      ],
      'year' => [
        'required',
        'int',
        'digits:4',
        // 프론트 확인 필요!!
        // function ($key, $value, $fail) {
        //   $yearCheck = SimulationApplicantStat::whereHas('season', function ($seasonQuery) use ($value) {
        //     $seasonQuery->currentSeasons(false)
        //       ->whereYear('first_started_at', $value);
        //   })
        //     ->whereHas('applicant', function ($applicantQuery) {
        //       $applicantQuery->where('user_id', $this->user_id);
        //     })
        //   ->exists();
        //   if (!$yearCheck) {
        //     $fail(__('The selected ' . $key . ' is invalid.'));
        //   }
        // },
      ],
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

    $latestEndedSeason = SimulationSeason::where('server', $applicant->server)
      ->orderByDesc('first_started_at')
      ->currentSeasons(false)
      ->first();

    $addParamArray = [
      'year' => $latestEndedSeason?->first_started_at->year ?? now()->year,
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

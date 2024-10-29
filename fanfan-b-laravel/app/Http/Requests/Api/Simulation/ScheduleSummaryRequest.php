<?php


namespace App\Http\Requests\Api\Simulation;

use App\Models\simulation\SimulationUserRank;

class ScheduleSummaryRequest extends ScheduleSummarySeasonRequest
{
  public function rules()
  {
    return [
      ...parent::rules(),
      'season' => [
        'required',
        'string',
        // Rule::exists(SimulationSeason::class, 'id'),
        function ($key, $value, $fail) {
          if (!$this->rulesForSeasonId($value)) {
            $fail(__('The selected ' . $key . ' is invalid.'));
          }
        },
      ]
    ];
  }

  private function rulesForSeasonId($value)
  {
    return SimulationUserRank::whereHas('league.season', function ($seasonQuery) use ($value) {
      $seasonQuery->currentSeasons(false)
        ->where('id', $value);
    })
      ->whereHas('applicant', function ($applicantQuery) {
        $applicantQuery->where('user_id', $this->user_id);
      })
      ->exists();
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

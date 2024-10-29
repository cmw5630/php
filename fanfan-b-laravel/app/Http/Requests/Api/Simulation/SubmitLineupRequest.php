<?php

namespace App\Http\Requests\Api\Simulation;

use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubmitLineupRequest extends FormRequest
{
  public function all($key = null)
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
      //신청 포메이션이 formation_used에 존재하는 가능한 포메이션인지
      'formation_used' => ['required', Rule::in(config('constant.LINEUP_FORMATION'))],
      'playing_style' => [
        'nullable',
        'min:1',
        'max:7'
      ],
      'defensive_line' => [
        'nullable',
        'min:1',
        'max:3'
      ],
      'substitution_count' => [
        'required',
        'int',
        'min:1',
        'max:3'
      ],
      'lineups' => [
        'required',
        'json'
      ],
      // 'lineups.*.id' => [
      //   'required',
      //   ],
      // 'lineups.*.sub_position' => [
      //   'required',
      // ],
      // 'lineups.*.place_index' => [
      //   'required',
      // ],
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

  protected function prepareForValidation()
  {
    // $this->merge([
    //   'lineups' => json_decode($this->input('lineups'), true)
    // ]);
    $addParamArray = [
      'playing_style' => 4,
      'defensive_line' => 2
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

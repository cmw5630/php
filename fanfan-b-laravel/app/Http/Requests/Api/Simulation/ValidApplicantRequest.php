<?php

namespace App\Http\Requests\Api\Simulation;
use App\Enums\Opta\YesNo;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ValidApplicantRequest extends FormRequest
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
      ]
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
    $addParamArray = [
      //
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

<?php

namespace App\Http\Requests\Api\Simulation;

use App\Enums\Nations;
use App\Http\Requests\FormRequest;
use App\Models\simulation\SimulationApplicant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterApplicantRequest extends FormRequest
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
      //대문자만 가능하도록
      'club_code_name' => ['string', 'required', 'regex:/^[A-Z]{3}$/'],
      'user_id' => ['unique:' . SimulationApplicant::getTableName() . ',user_id'],
      'server' => [
        'required', Rule::in(array_keys(config('simulationpolicies.server')))
      ]
    ];
  }

  public function messages()
  {
    return [
      'user_id.unique' => 'Already applied.',
    ];
  }

  public function attributes()
  {
    return [];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'server' => 'asia',
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

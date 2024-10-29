<?php

namespace App\Http\Requests\Api\Common;
use App\Http\Requests\FormRequest;
use App\Models\alarm\AlarmLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlarmUpdateRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['alarm_id'] = $this->route('id');
    return $request;
  }

  public function rules()
  {
    return [
      'alarm_id' => ['nullable',
        Rule::exists(AlarmLog::getTableName(),
          'id')->whereNull('deleted_at')
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

<?php

namespace App\Http\Requests\Api\Common;
use App\Http\Requests\FormRequest;
use App\Models\alarm\AlarmLog;
use Illuminate\Validation\Rule;

class AlarmListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'offset' => [
        'nullable',
        Rule::exists(AlarmLog::getTableName(), 'id')->whereNull('deleted_at')
      ],
      'limit' => $this->perPageRule(),
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
      'offset' => null,
      'limit' => 20,
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

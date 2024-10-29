<?php

namespace App\Http\Requests\Api\Common;
use App\Http\Requests\FormRequest;
use App\Models\Code;
use Illuminate\Validation\Rule;

class CodeListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'category' => [
        'nullable',
        'array',
        Rule::exists(Code::getTableName(), 'category')->whereNull('deleted_at')
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
      'category' => null,
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

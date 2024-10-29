<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;
use App\Models\user\User;
use Illuminate\Validation\Rule;

class LoginHistoryIpDetailReqeust extends FormRequest
{
  public function rules()
  {
    return [
      'ip_address' => ['nullable', 'ip'],
      'page' => ['integer', 'min:1'],
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
      'ip_address' => null,
      'page' => 1,
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

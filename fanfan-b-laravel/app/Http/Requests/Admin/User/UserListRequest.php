<?php

namespace App\Http\Requests\Admin\User;
use App\Enums\UserStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UserListRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['nullable', 'string'],
      'status' => ['nullable', 'array'],
      'status.*' => ['string', Rule::in(UserStatus::getValues())],
      'provider' => ['nullable', 'array'],
      'provider.*' => ['string', 'in:google,facebook,direct'],
      'limit' => ['nullable', 'integer', 'in:20,50,100'],
      'page' => ['integer', 'min:1']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function attributes()
  {
    return [
      'q' => 'Keyword',
      'status.*' => 'status',
      'provider.*' => 'provider'
    ];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'q' => null,
      'status' => null,
      'provider' => null,
      'limit' => 20,
      'page' => 1
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

<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;

class DraftPricesRequest extends FormRequest
{
  public function rules()
  {
    return [];
  }

  public function messages()
  {
    return [];
  }

  public function attributes()
  {
    return [];
  }

  protected function prepareForValidation(): void
  {
  }
}

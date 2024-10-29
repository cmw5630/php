<?php

namespace App\Http\Controllers\API;
use App\Http\Requests\FormRequest;

class HeaderSearchRequest extends FormRequest
{
  public function rules()
  {
    return [
      'q' => ['required', 'string'],
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
      'q' => 'Keyword'
    ];
  }
}

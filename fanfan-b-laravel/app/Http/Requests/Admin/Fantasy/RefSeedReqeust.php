<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;


class RefSeedReqeust extends FormRequest
{
  public function rules()
  {
    return [
      'ref_seed' => ['required', 'mimes:xlsx,xls'],
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
}

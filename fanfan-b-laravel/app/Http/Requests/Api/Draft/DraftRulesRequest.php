<?php

namespace App\Http\Requests\Api\Draft;

use App\Http\Requests\FormRequest;

class DraftRulesRequest extends FormRequest
{
  public function rules()
  {
    return [
      'player_id' => [
        'nullable',
        'string',
      ],
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

<?php

namespace App\Http\Requests\Admin\Fantasy;

use App\Http\Requests\FormRequest;

class QuestRequest extends FormRequest
{
  public function rules()
  {
    return [
      'quests' => ['required', 'json'],
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

<?php

namespace App\Http\Requests\Api\Game;

use App\Http\Requests\FormRequest;
use App\Models\game\FreeCardMeta;
use App\Models\game\FreeCardShuffleMemory;
use Illuminate\Validation\Rule;

class OpenFreeShuffleCardRequest extends FormRequest
{
  public function rules()
  {
    return [
      'game_id' => [
        'required',
        'integer',
        Rule::exists(FreeCardMeta::getTableName(), 'game_id')
      ],
      'shuffle_id' => [
        'nullable',
        'integer',
        Rule::exists(FreeCardShuffleMemory::getTableName(), 'id')->where('is_open', false)
      ]
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

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'shuffle_id' => null
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

<?php

namespace App\Http\Requests\Api\Stat;

use App\Http\Requests\FormRequest;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use Illuminate\Http\Request;

class PlayerDetailRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['player'] = $this->route('player_id');

    return $request;
  }

  public function rules()
  {
    $validation = [
      'player' => [
        'required', 'string', 'exists:' . PlateCard::getTableName() . ',player_id'
      ],
    ];

    if (strpos($this->path(), 'records')) {
      $validation = [
        ...$validation, 'season' => [
          'required', 'string', 'exists:' . Season::getTableName() . ',id'
        ]
      ];
    }

    return $validation;
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
      'season' => null,
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

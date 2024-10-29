<?php

namespace App\Http\Requests\Api\PlateCard;
use App\Http\Requests\FormRequest;
use App\Models\data\League;

class UserCardCountRequest extends FormRequest
{
  public function rules()
  {
    return [
      'league' => ['required', 'exists:' . League::getTableName() . ',id'],
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
      'league' => League::defaultLeague()->id,
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

<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Http\Requests\FormRequest;
use App\Models\game\PlateCard;
use App\Models\meta\RefPlayerCurrentMeta;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerInfoRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['player'] = $this->route('player_id');

    return $request;
  }

  public function rules()
  {
    return [
      'player' => [
        'required', 'string', 'exists:' . PlateCard::getTableName() . ',player_id'
      ],
      'season' => [
        'nullable', 'string', Rule::exists(RefPlayerCurrentMeta::getTableName(), 'target_season_id')
          ->where(function ($query) {
            $query->where('player_id', $this->player);
          })
      ],
      'user_plate_card' => [
        'nullable', 'int', 'exists:' . UserPlateCard::getTableName() . ',id'
      ],
      'mode' => ['nullable', 'string', 'in:history']
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
      'user_plate_card' => null,
      'mode' => null
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

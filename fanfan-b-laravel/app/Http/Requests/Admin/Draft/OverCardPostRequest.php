<?php

namespace App\Http\Requests\Admin\Draft;

use App\Http\Requests\FormRequest;
use App\Models\game\PlateCard;
use Illuminate\Validation\Rule;

class OverCardPostRequest extends FormRequest
{
  public function rules()
  {
    return [
      'plate_card_id' => [
        'required',
        'integer',
        Rule::exists(PlateCard::getTableName(), 'id')
          ->where(function ($query) {
            $query->where([
              ['player_id', $this->player_id],
              ['season_id', $this->season_id],
              ['team_id', $this->club],
            ]);
          })
      ],
      'player_id' => [
        'required',
        'string',
      ],
      'season_id' => ['required', 'string'],
      'club' => [
        'required',
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

  protected function prepareForValidation(): void
  {
  }
}

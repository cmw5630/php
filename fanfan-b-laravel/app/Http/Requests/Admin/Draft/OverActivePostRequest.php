<?php

namespace App\Http\Requests\Admin\Draft;

use App\Http\Requests\FormRequest;
use App\Models\data\Squad;
use App\Models\game\PlateCard;
use Illuminate\Validation\Rule;

class OverActivePostRequest extends FormRequest
{
  public function rules()
  {
    return [
      'squad_id' => [
        'required',
        'integer',
        Rule::exists(Squad::getTableName(), 'id')
          ->onlyTrashed()
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
        Rule::exists(PlateCard::getTableName(), 'player_id')
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

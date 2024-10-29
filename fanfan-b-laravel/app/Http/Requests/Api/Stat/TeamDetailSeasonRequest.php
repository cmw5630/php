<?php

namespace App\Http\Requests\Api\Stat;

use App\Http\Requests\FormRequest;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\data\Team;
use Illuminate\Http\Request;

class TeamDetailSeasonRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['team_id'] = $this->route('team_id');

    return $request;
  }

  public function rules()
  {
    return [
      'team_id' => [
        'required',
        'string',
        'exists:' . Team::getTableName() . ',id'
      ],
      'season' => [
        'nullable',
        'string',
        'exists:' . Season::getTableName() . ',id'
      ]
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
      'season' => SeasonTeam::where('team_id', $this->team_id)
        ->whereHas('season', function ($query) {
          $query->currentSeasons();
        })->value('season_id')
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

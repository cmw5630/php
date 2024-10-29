<?php

namespace App\Http\Requests\Api\Common;
use App\Http\Requests\FormRequest;

class RoundSchedulesRequest extends FormRequest
{
  public function rules()
  {
    return [
      'season' => ['required', 'string'],
      'round' => ['required', 'integer']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  // public function prepareForValidation(): void
  // {
  //   if ($this->request->has('season') && $this->request->has('round')) {
  //     return;
  //   }
  //
  //   $league = (new GameService)->leaguesWithRound()['EPL'];
  //
  //   $params = [
  //     'season' => $league['season_id'],
  //     'round' => $league['current_round'],
  //   ];
  //
  //   foreach ($params as $key => $value) {
  //     if (!isset($this->{$key})) {
  //       $this->merge([
  //         $key => $value
  //       ]);
  //     }
  //   }
  // }
}

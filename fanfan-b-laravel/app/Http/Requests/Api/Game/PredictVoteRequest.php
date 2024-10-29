<?php

namespace App\Http\Requests\Api\Game;
use App\Http\Requests\FormRequest;
use App\Models\log\PredictVote;
use App\Models\log\PredictVoteItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PredictVoteRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['vote_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'vote_id' => [
        'required',
        Rule::exists(PredictVote::class, 'id')
          ->where(function ($query) {
            $query->where('started_at', '<=', now())
              ->where('ended_at', '>=', now());
          })
        ->whereNull('deleted_at')
      ],
      'item' => ['array', 'min:1', 'max:2'],
      'item.*.id' => [
        'required',
        Rule::exists(PredictVoteItem::class, 'id')->where('predict_vote_id',
          $this->vote_id)->whereNull('deleted_at'),
      ],
      'item.*.answer' => [
        'required',
      ],
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
      //
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

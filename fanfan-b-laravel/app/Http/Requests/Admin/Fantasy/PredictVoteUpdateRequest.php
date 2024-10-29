<?php

namespace App\Http\Requests\Admin\Fantasy;
use App\Http\Requests\FormRequest;
use App\Models\game\PlateCard;
use App\Models\log\PredictVoteItem;
use App\Models\log\PredictVoteQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PredictVoteUpdateRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'title' => ['required'],
      'item' => ['array', 'min:1'],
      'item.*.id' => ['nullable', Rule::exists(PredictVoteItem::getTableName(), 'id')],
      'item.*.question' => [
        'required',
        Rule::exists(PredictVoteQuestion::getTableName(), 'id'),
      ],
      'item.*.option1' => ['required', Rule::exists(PlateCard::getTableName(), 'id')],
      'item.*.option2' => ['required', Rule::exists(PlateCard::getTableName(), 'id')],
      'item.*.answer' => ['nullable', 'in:1,2'],
      'ended_at' => ['required', 'date', 'after:today'],
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
      'id' => null,
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

<?php

namespace App\Http\Requests\Admin\Fantasy;
use App\Http\Requests\FormRequest;
use App\Models\log\PredictVote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PredictVoteDetailRequest extends FormRequest
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
      'id' => ['required', Rule::exists(PredictVote::getTableName(), 'id')],
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

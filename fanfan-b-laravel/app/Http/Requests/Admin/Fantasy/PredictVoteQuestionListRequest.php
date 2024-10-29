<?php

namespace App\Http\Requests\Admin\Fantasy;
use App\Http\Requests\FormRequest;
use Illuminate\Http\Request;

class PredictVoteQuestionListRequest extends FormRequest
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
      'question' => ['required'],
      'id' => ['nullable'],
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

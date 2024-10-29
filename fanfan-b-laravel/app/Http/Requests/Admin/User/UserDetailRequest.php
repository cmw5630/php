<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;
use App\Models\user\User;
use Illuminate\Http\Request;

class UserDetailRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'q' => ['nullable', 'string'],
      'mode' => ['required', 'in:point,join,card,trade,login'],
      'user_id' => ['required', 'exists:'.User::getTableName().',id'],
      'sub_mode' => ['nullable'],
      'page' => ['integer', 'min:1'],
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
      'q' => null,
      'mode' => 'point',
      'sub_mode' => null,
      'page' => 1
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

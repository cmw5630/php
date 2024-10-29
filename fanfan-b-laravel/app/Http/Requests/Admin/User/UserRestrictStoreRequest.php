<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;
use App\Models\user\User;
use Illuminate\Http\Request;

class UserRestrictStoreRequest extends FormRequest
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
      'user_id' => ['required', 'exists:'.User::getTableName().',id'],
      'reason' => ['required', $this->codeExists('R01')],
      'period' => ['required', $this->codeExists('R02')],
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

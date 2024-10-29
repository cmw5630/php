<?php

namespace App\Http\Requests\Admin\User;
use App\Http\Requests\FormRequest;
use App\Models\admin\UserRestriction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRestrictDeleteRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['user_restriction_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'user_restriction_id' => [
        'required',
        Rule::exists(UserRestriction::getTableName(), 'id')->whereNull('deleted_at'),
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

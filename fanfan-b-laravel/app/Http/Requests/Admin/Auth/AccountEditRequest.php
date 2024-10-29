<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\FormRequest;
use App\Models\admin\Admin;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AccountEditRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();

    if (!isset($request['id'])) {
      $request['user_id'] = (int) $this->route('id');
    }

    return $request;
  }

  public function rules()
  {
    return [
      'user_id' => ['required', 'int', 'exists:' . Admin::getTableName() . ',id'],
      'role' => ['required', 'int', Rule::exists('admin.roles', 'id')],
      'password' => ['nullable', 'string']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

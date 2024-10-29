<?php

namespace App\Http\Requests\Admin\Auth;
use App\Http\Requests\FormRequest;
use App\Models\admin\Admin;
use Illuminate\Validation\Rule;

class AccountStoreRequest extends FormRequest
{
  public function rules()
  {
    return [
      'login_id' => [Rule::unique(Admin::getTableName(), 'login_id')
        ->where(function ($query) {
          $query->whereNull('deleted_at');
        })]
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

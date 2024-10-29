<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\FormRequest;
use App\Interfaces\UserConstantInterface;
use App\Libraries\Traits\AuthTrait;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserWithdrawRequest extends FormRequest implements UserConstantInterface
{
  use AuthTrait;

  protected $userId;

  public function __construct(Request $request)
  {
    parent::__construct();
    $this->userId = $request->user()->id;
  }

  public function rules()
  {
    return [
      'reason' => [
        'required',
        'array',
        'between:1,3',
        $this->codeExists('W01'),
      ],
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function attributes()
  {
    return [];
  }
}

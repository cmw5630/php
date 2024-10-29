<?php

namespace App\Http\Requests\Api\User;

use App\Enums\UserStatus;
use App\Http\Requests\FormRequest;
use App\Interfaces\UserConstantInterface;
use App\Libraries\Traits\AuthTrait;
use App\Models\user\User;
use App\Models\user\UserReferral;
use Illuminate\Http\Request;

class UserValidationRequest extends FormRequest implements UserConstantInterface
{
  use AuthTrait;

  protected $userId;

  public function __construct(Request $request)
  {
    parent::__construct();
    if ($request->user()) {
      $this->userId = $request->user()->id;
    }
  }

  public function rules()
  {
    return [
      //
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function checkRules()
  {
    $rules = config('constant.validation.rules.user');
    // Signup
    $result = [
      'mode' => ['nullable', 'in:signup,modify_password,modify_info,reset_password,check_password'],
      'email' => [
        function ($key, $value, $fail) {
          $existUser = User::withoutGlobalScope('excludeWithdraw')->where('email', $value)->first();
          if (!is_null($existUser)) {
            if ($existUser->status === UserStatus::OUT) {
              $fail(__('validation.not_available', ['attribute' => 'email']));
              return;
            }
            $fail(__('validation.unique', ['attribute' => 'email']));
          }
        },
        // Rule::unique(User::getTableName(), 'email')->where(function ($query) {
        //   $query->where('status', '!=', UserStatus::OUT);
        // }),
        ...$rules['email']
      ],
      'new_password' => $this->newPasswordRule(),
      'password' => $this->passwordRule(),
      'name' => [
        function ($key, $value, $fail) {
          $existUser = User::withoutGlobalScope('excludeWithdraw')->where('name', $value)->first();
          if (!is_null($existUser)) {
            if ($existUser->status === UserStatus::OUT) {
              $fail(__('validation.not_available'));
              return;
            }
            $fail(__('validation.unique'));
          }
        },
        ...$rules['name']
      ],
    ];

    if (in_array($this->mode, ['modify_password', 'reset_password'])) {
      $result['new_password'][] = 'required';
    } else if ($this->mode === 'modify_info') {
      $result['photo'] = ['mimes:jpeg,png,bmp,tiff'];
    } else if ($this->mode === 'signup') {
      $result['referral_code']  = [
        'nullable',
        function ($key, $value, $fail) {
          $userExists = UserReferral::has('joinUser')->where('user_referral_code', $value)->first();
          if (is_null($userExists)) {
            $fail('This code does not exist.');
          }
        }
      ];
    }

    return $result;
  }

  public function checkMessages()
  {
    return [
      'name.regex' => ':Attribute can only be uppercase, lowercase, and numeric.',
      'password_confirm.same' => 'Please enter the same Password as above.'
    ];
  }

  public function attributes()
  {
    return [
      'name' => 'nickname',
    ];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'mode' => null,
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

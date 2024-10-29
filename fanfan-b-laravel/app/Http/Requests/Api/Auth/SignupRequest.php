<?php

namespace App\Http\Requests\Api\Auth;

use App\Enums\Nations;
use App\Enums\UserStatus;
use App\Http\Requests\FormRequest;
use App\Interfaces\UserConstantInterface;
use App\Libraries\Traits\AuthTrait;
use App\Models\data\SeasonTeam;
use App\Models\user\User;
use App\Models\user\UserReferral;
use Illuminate\Validation\Rule;

class SignupRequest extends FormRequest implements UserConstantInterface
{
  use AuthTrait;

  public function rules()
  {
    $rules = config('constant.validation.rules.user');
    return [
      'email' => [
        'required',
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
        ...$rules['email']
      ],
      'name' => [
        'required',
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
      'password' => ['required'] + $this->passwordRule(),
      'referral_code' => ['nullable', 'exists:' . UserReferral::getTableName() . ',user_referral_code'],
      'nation' => ['required', Rule::in(Nations::getValues())],
      'favorite_team' => ['required',
        function ($key, $value, $fail) {
          $exists = SeasonTeam::currentSeason()->where('team_id', $value)
            ->exists();

          if (!$exists) {
            $fail('The selected favorite team is invalid');
          }
        },
      ],
      'optional_agree' => ['required', 'boolean'],
    ];
  }

  public function messages()
  {
    return [
      'name.regex' => ':Attribute can only be uppercase, lowercase, and numeric.',
      'password_confirm.same' => 'Please enter the same Password as above.'
    ];
  }

  public function attributes()
  {
    return [
      'name' => 'username'
    ];
  }
}

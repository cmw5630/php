<?php

namespace App\Http\Requests\Api\Auth;
use App\Enums\Nations;
use App\Enums\UserStatus;
use App\Http\Requests\FormRequest;
use App\Models\data\SeasonTeam;
use App\Models\user\User;
use App\Models\user\UserReferral;
use Illuminate\Validation\Rule;

class SocialRequest extends FormRequest
{


  public function rules()
  {
    $rules = config('constant.validation.rules.user');
    return [
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
      'referral_code' => ['nullable', 'exists:' . UserReferral::getTableName() . ',user_referral_code'],
      'nation' => ['required', Rule::in(Nations::getValues())],
      'favorite_team' => [
        'required',
        Rule::exists(SeasonTeam::getTableName(), 'team_id')
          ->where(function ($query) {
            return (new SeasonTeam)->currentSeason($query);
          })
      ],
    ];
  }

  public function messages()
  {
    return [
      'name.regex' => ':Attribute can only be uppercase, lowercase, and numeric.',
      'referral_code.exists' => 'This :Attribute does not exist.'
    ];
  }

  public function attributes()
  {
    return [
      'name' => 'username',
      'referral_code' => 'code'
    ];
  }



}

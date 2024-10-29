<?php

namespace App\Http\Requests\Api\User;
use App\Enums\RedeemStatus;
use App\Http\Requests\FormRequest;
use App\Models\admin\Redeem;
use Illuminate\Validation\Rule;

class UserRedeemRequest extends FormRequest
{
  public function rules()
  {
    return [
      'redeem_code' => [
        'required',
        Rule::exists(Redeem::getTableName(), 'redeem_code')
          ->where(function ($query) {
            $query->where('status', RedeemStatus::ACTIVE);
          }),
      ],
    ];
  }

  public function messages()
  {
    return [

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

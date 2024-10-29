<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Enums\Opta\Card\DraftCardStatus;
use App\Http\Requests\FormRequest;
use App\Models\data\Schedule;
use App\Models\user\UserPlateCard;
use Illuminate\Validation\Rule;

class MyCardOpenRequest extends FormRequest
{
  public function rules()
  {
    return [
      'id' => [
        'nullable',
        Rule::requiredIf(is_null($this->schedule_id)),
        'prohibits:type',
        'int',
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('is_open', false)
              ->where('status', DraftCardStatus::COMPLETE)
              ->where('user_id', $this->user()->id);
          })
      ],
      'schedule_id' => [
        'nullable',
        Rule::requiredIf(is_null($this->id)),
        'prohibits:id',
        'string',
        'exists:' . Schedule::getTableName() . ',id'
      ],
    ];
  }

  public function messages()
  {
    return [];
  }

  public function attributes()
  {
    return [];
  }

  protected function prepareForValidation(): void
  {
    $addParamArray = [
      'id' => null,
      'schedule_id' => null,
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

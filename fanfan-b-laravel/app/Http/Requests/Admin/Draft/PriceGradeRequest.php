<?php

namespace App\Http\Requests\Admin\Draft;

use App\Enums\Opta\Card\OriginGrade;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class PriceGradeRequest extends FormRequest
{
  public function rules()
  {
    return [
      'grade' => ['required', Rule::in(OriginGrade::getValues())],
      'gold' => ['required', 'integer'],
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
  }
}

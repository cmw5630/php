<?php

namespace App\Http\Requests\Admin\Market;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Http\Requests\FormRequest;
use Arr;
use Illuminate\Validation\Rule;

class AuctionListRequest extends FormRequest
{
  public function rules()
  {
    // dd(array_values(Arr::except(AuctionStatus::getValues(),
    //   [array_search(AuctionStatus::EXPIRED, AuctionStatus::getValues()), array_search(AuctionStatus::DISABLED, AuctionStatus::getValues())])));
    return [
      'type' => ['nullable', Rule::in(AuctionType::getValues())],
      'status' => ['nullable',
        Rule::in(array_values(Arr::except(AuctionStatus::getValues(),
          [
            array_search(AuctionStatus::EXPIRED, AuctionStatus::getValues()),
            array_search(AuctionStatus::DISABLED, AuctionStatus::getValues())
          ])))
      ],
      'page' => ['integer', 'min:1'],
      'per_page' => $this->perPageRule(),
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
      'type' => null,
      'status' => null,
      'q' => null,
      'page' => 1,
      'per_page' => 20,
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

<?php

namespace App\Http\Requests\Api\Market;

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use App\Models\user\UserPlateCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketStoreRequest extends FormRequest
{
  public function all($keys = null)
  {
    $request = Request::all();
    $request['auction_id'] = $this->route('id');

    return $request;
  }

  public function rules()
  {
    return [
      'auction_id' => [
        'nullable',
        'prohibits:user_plate_card',
        Rule::exists(Auction::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('expired_at', '<', now())
              ->where('status', AuctionStatus::EXPIRED)
              ->whereNull('deleted_at');
          })
      ],
      'user_plate_card' => [
        'nullable',
        'prohibits:auction_id',
        Rule::exists(UserPlateCard::getTableName(), 'id')
          ->where('user_id', auth()->user()->id),
        // 'exists:' . UserPlateCard::getTableName() . ',id',
        // 취소건 제외하고는 같은 id가 있어선 안됨., 판매된 카드 역시 제외 
        // Rule::unique(Auction::getTableName(), 'user_plate_card_id')
        //   ->where(fn($query) => $query->where(fn($q) => $q->whereNull('sold_at')
        //     ->whereNull('canceled_at')
        //   )
        //     ->orWhereNull('expired_at'))
        //   ->whereNull('deleted_at'),
        // ->where(fn ($query) => $query->orWhereNull('expired_at')),
        Rule::unique(Auction::getTableName(), 'user_plate_card_id')
          ->where('status', AuctionStatus::BIDDING)
          ->whereNull('deleted_at'),
      ],
      'type' => ['nullable', 'in:' . implode(',', AuctionType::getValues())],
      'start_price' => ['required', 'integer', 'min:1'],
      'buynow_price' => ['required', 'integer', 'min:1'],
      'period' => ['required', 'integer', 'in:24,48,72']
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }

  public function prepareForValidation(): void
  {
    $addParamArray = [
      'auction_id' => null,
      'type' => 'open',
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

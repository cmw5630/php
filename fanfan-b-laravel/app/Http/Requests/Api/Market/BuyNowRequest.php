<?php

namespace App\Http\Requests\Api\Market;
use App\Enums\AuctionStatus;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuyNowRequest extends FormRequest
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
        'required',
        Rule::exists(Auction::getTableName(), 'id')
          ->where(function ($query) {
            $query->where('expired_at', '>', now());
          }),
        function ($key, $value, $fail) {
          $auctionStatus = Auction::where('id', $value)->value('status');
          if ($auctionStatus === AuctionStatus::CANCELED) {
            $fail('The selected ' . $key . ' is ' . $auctionStatus);

            return;
          }
          if ($auctionStatus === AuctionStatus::SOLD) {
            $fail('The selected ' . $key . ' is ' . $auctionStatus);

            return;
          }
          if ($auctionStatus !== AuctionStatus::BIDDING) {
            $fail(__('validation.exists', ['attribute' => 'auction_id']));
          }
        }
      ]
    ];
  }

  public function messages()
  {
    return [
      //
    ];
  }
}

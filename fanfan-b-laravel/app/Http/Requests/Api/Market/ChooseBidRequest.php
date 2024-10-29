<?php

namespace App\Http\Requests\Api\Market;
use App\Enums\AuctionType;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use App\Models\game\AuctionBid;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChooseBidRequest extends FormRequest
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
      'auction_id' => ['required', Rule::exists(Auction::getTableName(), 'id')
        ->where(function ($query) {
          $query->whereNull('deleted_at')
            ->where('type', AuctionType::BLIND);
        })],
      'bid_id' => ['required', Rule::exists(AuctionBid::getTableName(), 'id')
        ->where(function ($query) {
          $query->whereNull('deleted_at')
            ->where('auction_id', $this->auction_id);
        })],
    ];
  }
  public function messages()
  {
    return [
      //
    ];
  }
}

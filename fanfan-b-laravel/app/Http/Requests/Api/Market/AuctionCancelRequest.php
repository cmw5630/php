<?php

namespace App\Http\Requests\Api\Market;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuctionCancelRequest extends FormRequest
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
            // 취소건 아님
            ->whereNull('canceled_at')
            // 30분 이내
            ->where('created_at', '<=', now()->addMinutes(30));
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

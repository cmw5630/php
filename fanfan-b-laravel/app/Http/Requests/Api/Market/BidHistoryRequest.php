<?php

namespace App\Http\Requests\Api\Market;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BidHistoryRequest extends FormRequest
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
        $query->whereNull('deleted_at');
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

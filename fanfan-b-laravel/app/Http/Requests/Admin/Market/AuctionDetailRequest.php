<?php

namespace App\Http\Requests\Admin\Market;
use App\Http\Requests\FormRequest;
use App\Models\game\Auction;
use Illuminate\Http\Request;

class AuctionDetailRequest extends FormRequest
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
      'auction_id' => ['required', 'exists:'. Auction::getTableName().',id'],
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
      'page' => 1,
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

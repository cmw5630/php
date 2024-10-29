<?php

namespace App\Http\Requests\Api\PlateCard;

use App\Enums\PointType;
use App\Enums\PurchaseOrderType;
use App\Http\Requests\FormRequest;
use App\Models\game\PlateCard;
use App\Models\order\Cart;
use Illuminate\Validation\Rule;

class PlateCardOrderRequest extends FormRequest
{
  public function rules()
  {
    return [
      'type' => ['required', 'string', Rule::in(PurchaseOrderType::getValues())],
      'cart' => [Rule::requiredIf($this->type === PurchaseOrderType::CART), 'array', 'exists:' . Cart::getTableName() . ',id'],
      'plate_card_id' => [Rule::requiredIf($this->type === 'direct'), 'int', 'exists:' . PlateCard::getTableName() . ',id'],
      'quantity' => [Rule::requiredIf($this->type === PurchaseOrderType::DIRECT), 'int', 'min:1', 'max:20'],
      'point_type' => ['required', 'string', Rule::in(PointType::getValues())],
      'total_price' => ['required', 'int'],
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
}

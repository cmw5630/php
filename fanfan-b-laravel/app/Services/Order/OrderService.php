<?php

namespace App\Services\Order;

use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\PurchaseOrderType;
use App\Enums\QuestCollectionType;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\QuestRecorder;
use App\Libraries\Traits\LogTrait;
use App\Models\game\PlateCard;
use App\Models\log\UserPlateCardLog;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\order\Cart;
use App\Models\order\Order;
use App\Models\user\UserPlateCard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface OrderServiceInterface
{
  public function addCart(array $input);
  public function getCarts(): array;
  public function deleteCart(int $_cartId);
  public function updateCart(array $input);
  public function saveOrder(array $input);
}

class OrderService implements OrderServiceInterface
{
  use LogTrait;

  private ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }

  public function addCart($input)
  {
    try {
      $isExistCart = Cart::where([
        'user_id' => $this->user->id,
        'plate_card_id' => $input['plate_card_id']
      ]);
      if ($isExistCart->exists()) {
        $cart = $isExistCart->first();
        $totalCnt = $cart->quantity + $input['quantity'];
        if ($totalCnt > 20) $totalCnt = 20;
        $cart->quantity = $totalCnt;
      } else {
        $plateCardInfo = PlateCard::where('id', $input['plate_card_id'])->first();

        $cart = new Cart();
        $cart->user_id = $this->user->id;
        $cart->plate_card_id = $input['plate_card_id'];
        $cart->price = $plateCardInfo['price'];
        $cart->quantity = $input['quantity'];
      }
      $cart->save();

      logger('PlateCard Add Cart >> ' . $input['plate_card_id']);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getCarts(): array
  {
    try {
      return Cart::with('plateCard:id,player_id,player_name,price,team_name,team_short_name')
        ->whereUserId($this->user->id)
        ->get()
        ->toArray();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  private function checkMyCart($_cartData)
  {
    return $_cartData->user_id === $this->user->id;
  }

  public function deleteCart($_cartId)
  {
    $cartInfo = Cart::find($_cartId);

    try {
      if (!$this->checkMyCart($cartInfo)) {
        throw new Exception('님의 장바구니가 아니다(임시 텍스트)');
      }

      $cartInfo->delete();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function updateCart($input)
  {
    try {
      $cartInfo = Cart::find($input['cart_id']);
      if ($this->checkMyCart($cartInfo)) {
        $totalCnt = $cartInfo->quantity + $input['value'];
        if ($totalCnt > 20) $totalCnt = 20;
        if ($totalCnt < 1) $totalCnt = 1;
        $cartInfo->quantity = $totalCnt;
        $cartInfo->save();
      }
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function saveOrder($input)
  {
    try {
      [$orderItems, $totalPrice] = $this->getOrderItems($input);

      if ((int)$input['total_price'] !== $totalPrice) {
        throw new Exception('가격이 바뀌었다!(임시 텍스트)' . $totalPrice);
      }

      // 갱신된 price 값 사용해야함.
      $order = new Order();
      $order->user_id = $this->user->id;
      $order->total_price = $totalPrice;
      $order->save();

      foreach ($orderItems as $item) {
        // Event Observer 감지 안된다면 수정 필요
        $order->orderPlateCard()->create([
          'league_id' => $item['plate_card']['league_id'],
          'team_id' => $item['plate_card']['team_id'],
          'plate_card_id' => $item['plate_card']['id'],
          'grade' => $item['plate_card']['grade'],
          'position' => $item['plate_card']['position'],
          'price' => $item['plate_card']['price'],
          'quantity' => $item['quantity']
        ]);
        // $orderPlateCard->plate_card_id = $item['plate_card']['id'];
        // $orderPlateCard->price = $item['plate_card']['price'];
        // $orderPlateCard->quantity = $item['quantity'];
        // $orderPlateCard->save();

        $userPlateCard = new UserPlateCard();
        $userPlateCard->user_id = $this->user->id;
        $userPlateCard->plate_card_id = $item['plate_card']['id'];
        $userPlateCard->player_name = $item['plate_card']['first_name'] . ' ' . $item['plate_card']['last_name'];
        $playerOverall = RefPlayerOverallHistory::where([
          ['player_id', $item['plate_card']['player_id']],
          ['is_current', true]
        ])->first();
        $userPlateCard->ref_player_overall_history_id = $playerOverall->id;
        $userPlateCard->order_overall = $playerOverall->final_overall;
        $userPlateCard->order_league_id = $item['plate_card']['league_id'];
        $userPlateCard->order_team_id = $item['plate_card']['team_id'];
        $userPlateCard->card_grade = CardGrade::NONE;
        $userPlateCard->position = $item['plate_card']['position'];
        $userPlateCard->status = PlateCardStatus::PLATE;
        $userPlateCard->save();

        Schema::connection('log')->disableForeignKeyConstraints();

        $this->recordLog(UserPlateCardLog::class, [
          'user_id' => $this->user->id,
          'user_plate_card_id' => $userPlateCard->id
        ]);

        // 동일 카드 복수 구매
        $count = $item['quantity'] - 1;
        while ($count > 0) {
          $copy = $userPlateCard->replicate();
          $copy->save();

          $this->recordLog(UserPlateCardLog::class, [
            'user_id' => $this->user->id,
            'user_plate_card_id' => $copy->id
          ]);
          $count--;
        }

        // quest
        (new QuestRecorder())->act(QuestCollectionType::PLATE_BUY, $this->user->id, $item['quantity']);
      }

      Schema::connection('log')->enableForeignKeyConstraints();

      return [$order, $userPlateCard];
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  private function getOrderItems($input)
  {
    $orderItems = [];
    try {
      if ($input['type'] === PurchaseOrderType::CART) {
        $totalPrice = 0;
        $cartData = Cart::whereIn('id', $input['cart'])
          ->get();

        if ($cartData->count() < 1) {
          throw new Exception('유효하지 않은 장바구니 데이터(임시 텍스트)');
        }

        $cartData->map(function ($info) use (&$totalPrice, &$orderItems) {
          $totalPrice += $info->plateCard->price * $info->quantity;
          $orderItems[] = [
            'quantity' => $info->quantity,
            'plate_card' => $info->plateCard->toArray(),
          ];
          $this->deleteCart($info['id']);
        });
      } else {
        $plateCard = PlateCard::isOnsale()
          ->where('id', $input['plate_card_id'])->first();
        $totalPrice = $plateCard->price * $input['quantity'];

        $orderItems[] = [
          'quantity' => $input['quantity'],
          'plate_card' => $plateCard->toArray()
        ];
      }
    } catch (Exception $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return [$orderItems, $totalPrice];
  }
}

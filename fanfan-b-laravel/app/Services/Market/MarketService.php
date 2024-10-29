<?php

namespace App\Services\Market;

use App\Enums\AuctionBidStatus;
use App\Enums\AuctionStatus;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\GradeCardLockStatus;
use App\Libraries\Traits\LogTrait;
use App\Models\game\Auction;
use App\Models\log\UserPlateCardLog;
use App\Models\meta\RefMarketExpireReduction;
use App\Models\meta\RefMarketMinimumPrice;
use App\Models\user\UserPlateCard;
use Schema;

class MarketService
{
  use LogTrait;

  protected $reductionTable = null;
  protected $minimumPriceTable = null;

  public function highestBidProcess(Auction $_auction): void
  {
    $auctionBid = $_auction->auctionBid;
    if ($auctionBid->count() < 1) {
      $_auction->status = AuctionStatus::EXPIRED;

      $socketData = [
        'template_id' => 'market-sell-expired',
        'target_user_id' => $_auction->user_id,
        'dataset' => [
          'player' => $_auction->userPlateCard->plateCard->toArray(),
        ],
      ];

      $userPlateCard = $_auction->userPlateCard;
      // bid 실패이므로 min_price 초기화
      [$minPrice,] = $this->getMarketDataset($userPlateCard, $_auction->expired_count);
      $userPlateCard->min_price = $minPrice;
      $userPlateCard->save();

      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);
    } else {
      $_auction->status = AuctionStatus::SOLD;
      $highest = $auctionBid->sortByDesc('price')->first();
      $highest->status = AuctionBidStatus::SUCCESS;
      $highest->save();
      $_auction->sold_at = now();
      $userPlateCard = $_auction->userPlateCard;
      $userPlateCard->user_id = $highest->user_id;
      // bid 성공이므로 min_price 초기화
      [$minPrice,] = $this->getMarketDataset($userPlateCard);
      $userPlateCard->min_price = $minPrice;
      $userPlateCard->save();

      $this->userPlateCardLog($_auction->user_plate_card_id, $highest->user_id);

      // 구매자 노티
      $socketData = [
        'template_id' => 'market-buy-complete',
        'target_user_id' => $highest->user_id,
        'dataset' => [
          'player' => $userPlateCard->plateCard->toArray(),
        ],
      ];

      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);

      // 판매자 노티
      $socketData = [
        'template_id' => 'market-sell-complete',
        'target_user_id' => $_auction->user_id,
        'dataset' => [
          'player' => $userPlateCard->plateCard->toArray(),
        ],
      ];
      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);
    }

    __endUserPlateCardLock($_auction->user_plate_card_id, GradeCardLockStatus::MARKET);
    $_auction->save();
  }

  public function userPlateCardLog($_userPlateCardId, $_userId)
  {
    Schema::connection('log')->disableForeignKeyConstraints();
    $userPlateCardLog = new UserPlateCardLog;
    $userPlateCardLog->user_id = $_userId;
    $userPlateCardLog->user_plate_card_id = $_userPlateCardId;
    $userPlateCardLog->save();
    Schema::connection('log')->enableForeignKeyConstraints();
  }

  public function getMarketDataset(UserPlateCard $_userPlateCard, $_registerCount = 0)
  {
    if (is_null($this->reductionTable)) {
      $this->getReductionData();
    }
    if (is_null($this->minimumPriceTable)) {
      $this->getMinimunPriceData();
    }

    $draftType = 'single';

    if (
      $_userPlateCard->draft_level !== max([
        $_userPlateCard->{FantasyDraftCategoryType::ATTACKING},
        $_userPlateCard->{FantasyDraftCategoryType::DEFENSIVE},
        $_userPlateCard->{FantasyDraftCategoryType::DUEL},
        $_userPlateCard->{FantasyDraftCategoryType::PASSING},
        $_userPlateCard->{FantasyDraftCategoryType::GOALKEEPING},
      ])
    ) {
      $draftType = 'combined';
    }

    // draft_level 0은 1과 동일
    $draftLevel = max($_userPlateCard->draft_level, 1);
    $minGold = $this->minimumPriceTable[$_userPlateCard->card_grade][$draftLevel][$draftType][0]['min_gold'];
    $reduction = $this->reductionTable[$_registerCount];

    if ($_registerCount > 0) {
      $minGold = (int) round((float) bcmul($minGold, $reduction['reduction_rate']), -3);
    }

    if ($_userPlateCard->is_mom) {
      $minGold = (int) round((float) bcmul($minGold, 1.05), -3);
    }

    return [$minGold, $reduction['period_options']];
  }

  private function getReductionData()
  {
    $this->reductionTable = RefMarketExpireReduction::all()
      ->keyBy('expired_count')
      ->toArray();
  }

  private function getMinimunPriceData()
  {
    $this->minimumPriceTable = RefMarketMinimumPrice::all()
      ->groupBy(['card_grade', 'draft_level', 'draft_type'])
      ->toArray();
  }
}

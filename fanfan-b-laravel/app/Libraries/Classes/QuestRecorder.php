<?php

namespace App\Libraries\Classes;

use App\Enums\QuestCollectionType;
use App\Enums\QuestCycleType;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\game\Quest;
use App\Models\game\QuestType;
use App\Models\game\QuestUserAchievement;
use App\Models\game\QuestUserLog;
use Carbon\Carbon;
use Str;


class QuestRecorder
{
  use GameTrait, LogTrait;

  protected $questType;

  public function act(string $_questType, int $_userId, int $_count = 1)
  {
    if (!in_array($_questType, QuestCollectionType::getValues())) {
      throw new Exception('type error');
    }

    if (!User::whereId($_userId)->exists()) {
      throw new Exception('not user');
    }

    $this->questType = $_questType;
    if ($_questType === QuestCollectionType::PLATE_BUY) {
      $this->{Str::camel($_questType)}($_userId, $_count);
    } else {
      $this->{Str::camel($_questType)}($_userId);
    }
  }

  private function baseQuest()
  {
    $dateStringSet = $this->getDateStringSet();
    // 현재 quest 내용
    return QuestType::withWhereHas('quest', function ($query) {
      $query->where('code', $this->questType);
    })
      ->where([
        'start_date' => $dateStringSet['this_week']['start'],
        'end_date' => $dateStringSet['this_week']['end']
      ]);
  }

  private function login($_userId)
  {
    $baseQuery = $this->baseQuest();
    $now = now();
    if ($baseQuery->exists()) {
      $baseQuery->get()
        ->map(function ($weekQuest) use ($_userId, $now) {
          $this->saveUserAchievement($_userId, $weekQuest);
          if (!QuestUserLog::where([
            ['user_id', $_userId],
            ['quest_type_id', $weekQuest->id]
          ])
            ->whereBetween('created_at',
              [$now->clone()->startOfDay(), $now->clone()->endOfDay()])->exists()) {
            $this->saveUserLog($_userId, $weekQuest->id);
            $this->saveUserAchievement($_userId, $weekQuest);
          }
        });
    }
  }

  private function plateBuy($_userId, $_count = 1)
  {
    $baseQuery = $this->baseQuest();
    if ($baseQuery->exists()) {
      $baseQuery->get()
        ->map(function ($weekQuest) use ($_userId, $_count) {
          $this->saveUserLog($_userId, $weekQuest->id, $_count);
          $this->saveUserAchievement($_userId, $weekQuest);
        });
    }
  }

  private function upgrade($_userId)
  {
    $baseQuery = $this->baseQuest();
    if ($baseQuery->exists()) {
      $baseQuery->get()
        ->map(function ($weekQuest) use ($_userId) {
          $this->saveUserLog($_userId, $weekQuest->id);
          $this->saveUserAchievement($_userId, $weekQuest);
        });
    }
  }

  private function participation($_userId)
  {
    $baseQuery = $this->baseQuest();
    if ($baseQuery->exists()) {
      $baseQuery->get()
      ->map(function ($weekQuest) use ($_userId) {
        $this->saveUserLog($_userId, $weekQuest->id);
        $this->saveUserAchievement($_userId, $weekQuest);
      });
    }
  }

  private function transfer($_userId)
  {
    $baseQuery = $this->baseQuest();
    if ($baseQuery->exists()) {
      $baseQuery->get()
        ->map(function ($weekQuest) use ($_userId) {
          $this->saveUserLog($_userId, $weekQuest->id);
          $this->saveUserAchievement($_userId, $weekQuest);
        });
    }
  }

  private function saveUserLog($_userId, $_questId, $_count = 1)
  {
    while ($_count > 0) {
      $this->recordLog(QuestUserLog::class, [
        'user_id' => $_userId,
        'quest_type_id' => $_questId
      ]);
      $_count--;
    }
  }

  private function saveUserAchievement($_userId, $weekQuest)
  {
    $myLog = QuestUserLog::where([
      ['user_id', $_userId],
      ['quest_type_id', $weekQuest->id]
    ])->whereBetween('created_at', [Carbon::parse($weekQuest->start_date)->startOfDay(), Carbon::parse($weekQuest->end_date)->endOfDay()]);

    if ($weekQuest->quest->achieve_count <= $myLog->count()) {
      if (!QuestUserAchievement::where([
        ['user_id', $_userId],
        ['quest_type_id', $weekQuest['id']]
      ])->exists()) {
        $lastTime = $myLog->latest()->value('created_at');
        $quAchievement = new QuestUserAchievement();
        $quAchievement->user_id = $_userId;
        $quAchievement->quest_type_id = $weekQuest->id;
        $quAchievement->created_at = $lastTime;
        $quAchievement->save();
      }
    }
  }
}

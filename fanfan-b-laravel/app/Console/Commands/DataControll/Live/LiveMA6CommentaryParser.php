<?php

namespace App\Console\Commands\DataControll\Live;

use App\Console\Commands\OptaParsers\MA6CommentaryParser;
use App\Enums\System\SocketChannelType;
use App\Events\IngameSocketEvent;
use App\Models\data\Commentary;
use App\Models\game\PlateCard;

class LiveMA6CommentaryParser extends MA6CommentaryParser
{
  protected $ids;
  protected $targetQueue;

  public function __construct(string $_scheduleId, string $_targetQueue)
  {
    parent::__construct();
    $this->ids = [$_scheduleId];
    $this->targetQueue = $_targetQueue;
  }

  private function getPubSetBase(array $_datas, string $_type): array
  {
    $commonRowOrigin = $_datas['commonRowOrigin'];
    // $scheduleId = $commonRowOrigin['schedule_id'];
    $result = [
      'type' => $_type,
      'league_id' => $commonRowOrigin['league_id'],
      'season_id' => $_datas['commonRowOrigin']['season_id'],
      'schedule_id' => $commonRowOrigin['schedule_id'],
      'target_queue' => $this->targetQueue,
      'data_updated' => [],
    ];
    return $result;
  }

  private function getCommentaryPubData($_datas): array|null
  {
    $scheduleId =  $_datas['commonRowOrigin']['schedule_id'];
    $oldCommentIds = Commentary::where('schedule_id', $scheduleId)
      ->pluck('comment_id')->toArray();

    $pureNewCommnets = [];
    if (isset($_datas['specifiedAttrs']['message'])) {
      foreach ($_datas['specifiedAttrs']['message'] as $commentSet) {
        if (!in_array($commentSet['comment_id'], $oldCommentIds)) {
          $pureNewCommnets[] = $commentSet;
        }
      }
    }
    if (empty($pureNewCommnets)) {
      return null;
    }
    return $pureNewCommnets;
  }



  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->preProcessResponse($urlKey, $response);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      $pubSet = $this->getPubSetBase($datas, SocketChannelType::COMMENTARY);
      // data 체크<-
      $dataUpdated = $this->getCommentaryPubData($datas);
      if ($dataUpdated) {
        $homeTeamId = $datas['commonRowOrigin']['home_team_id'];
        $awayTeamId = $datas['commonRowOrigin']['away_team_id'];

        // 보정
        foreach ($dataUpdated as &$comment) {
          foreach (['1', '2'] as $playerRefNum) {
            if (isset($comment['player_ref' . $playerRefNum])) {
              $playerId = $comment['player_ref' . $playerRefNum];
              unset($comment['player_ref' . $playerRefNum]);
              $pcInst = PlateCard::wherePlayerId($playerId)->first();
              foreach (config('commonFields.player') as $colName) {
                $name = $pcInst?->{$colName} ?? '';
                $comment['player_ref' . $playerRefNum][$colName] = $name;
              }
              $comment['player_ref' . $playerRefNum]['player_id'] = $pcInst?->player_id ?? $playerId;
              $comment['player_ref' . $playerRefNum]['headshot_path'] = $pcInst?->headshot_path ?? null;
              $comment['player_ref' . $playerRefNum]['team_id'] = $pcInst?->team_id ?? null;
            }
          }
          $eventTeam = 'common';
          if (
            // isset($comment['team_ref1']) &&
            (isset($comment['player_ref1']) || isset($comment['player_ref2']))
          ) {
            if ($comment['player_ref1']['team_id'] === $homeTeamId) {
              $eventTeam = 'home';
            } else if ($comment['player_ref1']['team_id'] === $awayTeamId) {
              $eventTeam = 'away';
            }
          }
          $comment['event_team'] = $eventTeam;
          logger($comment);
        }

        $pubSet['data_updated'] = $dataUpdated;
        logger($pubSet);
        broadcast(new IngameSocketEvent($pubSet));
      }

      $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
    }
  }

  protected function parse(bool $_act): bool
  {
    // 비동기 동시처리 수로 쪼개기
    $ids = $this->ids;
    logger('commentary ids:' . json_encode($ids));

    // optaParser 설정 -->>
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setKGsToCustom(['matchInfo/contestant', '/messages']);
    // optaParser 설정 <<--

    $responses = $this->optaRequest($ids);
    try {
      $this->insertOptaDatasToTables($responses, null, [
        [
          'specifiedInfoMap' => [
            'message' => Commentary::class
          ],
          'conditions' => ['comment_id']
        ]
      ], $_act);
    } catch (\Exception $e) {
      logger('MA6Commentary 파서 에러');
      logger($e);
    }
    return true;
  }
}

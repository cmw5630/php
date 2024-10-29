<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\YesNo;
use App\Models\data\Squad;
use App\Models\game\PlateCard;
use App\Models\log\TeamChangeHistory;
use DB;
use Model;

class PlateCardBase
{
  public $squadPlateCommonColumns;


  public function __construct()
  {
    $this->squadPlateCommonColumns = $this->getCommonColumns(Squad::class, PlateCard::class);
  }

  private function collectPlayersTeamChanged(string $_playerId, string $_beforTeamId, string|null $_currentTeamId): void
  {
    $tchInst = (new TeamChangeHistory);
    $tchInst->player_id = $_playerId;
    $tchInst->before_team_id = $_beforTeamId;
    $tchInst->current_team_id = $_currentTeamId;
    $tchInst->save();
  }

  public function getCommonColumns($_tableOne, $_tableOther)
  {
    $table1 = (new $_tableOne)->getTableColumns(true);
    $table2 = (new $_tableOther)->getTableColumns(true);
    return array_intersect($table1, $table2);
  }


  public function getDiffColumns($_tableOne, $_tableOther)
  {
    $table1 = (new $_tableOne)->getTableColumns(true);
    $table2 = (new $_tableOther)->getTableColumns(true);
    return array_diff($table1, $table2);
  }

  private function checkTransfer($_existsPlateCard, $_oneRow)
  {
    $exCard = $_existsPlateCard->first();
    $playerId = $_oneRow['player_id'];
    $beforeTeam =  $exCard->team_id;
    $currentTeam =  $_oneRow['team_id'];
    if ($beforeTeam !== $currentTeam) { // 팀변경 감지
      $this->collectPlayersTeamChanged($playerId, $beforeTeam, $currentTeam);
    }
  }


  public function upsertOnePlateCard(array $_originSquadRow)
  {
    $oneRow = [];
    foreach ($this->squadPlateCommonColumns as $idx => $colName) {
      if ($colName === 'id') {
        continue;
      }
      $oneRow[$colName] = $_originSquadRow[$colName];
    }
    if (isset($_originSquadRow['nationality_code'])) {
      $oneRow['nationality_code'] = $_originSquadRow['nationality_code'];
    }

    // $oneRow['id'] = $_originSquadRow->player_id;
    $oneRow['league_code'] = $_originSquadRow['league']['league_code'];
    $oneRow['match_name_eng'] = __removeAccents($_originSquadRow['match_name']);
    $oneRow['first_name_eng'] = __removeAccents($_originSquadRow['first_name']);
    $oneRow['last_name_eng'] = __removeAccents($_originSquadRow['last_name']);
    // plate_cards insert 또는 update 로그 남기기
    if (!is_null($existsPlateCard = $this->isPlateCardAleadyExists($oneRow))) {
      $this->checkTransfer($existsPlateCard->clone(), $oneRow);
      $existsPlateCard->restore();
      $existsPlateCard->update($oneRow);
      logger('exist card updated!:' . $_originSquadRow['player_id']);
      /**
       * - player_id가 primary key로 바뀌기 전 내용(참고용 주석) -
       * plate_cards는 squads로부터 생성되므로 테이블의 데이터를 임의로 조작 또는 opta데이터 이상이 없다면,
       * 한번 insert된 동일한 선수정보(season_id, team_id, player_id)가 중복등록(insert)되는 경우는 없어야 한다.
       * (왜냐면, 한번 등록되면 그 다음부터는 update를 통한 상태(status, active)변경->트리거를 통해 처리되므로)
       * 다만 운영중 squads 데이터가 소실된다면 소실된 정보에 한해서 다음 수집시 plate_cards에 insert가 일어나게 되므로 이에대한 로그 기록 및 처리가 필요.
       */
    } else {
      PlateCard::create($oneRow);
    }
  }


  public function deleteTargetPlateCard(array $_statusLogRow): int
  {
    $card = PlateCard::where('season_id', '=', $_statusLogRow['season_id'])
      ->where('team_id', '=', $_statusLogRow['team_id'])
      ->where('player_id', '=', $_statusLogRow['player_id']);

    $update = [];
    $update['status'] = $_statusLogRow['status'];
    $update['active'] = $_statusLogRow['active'];
    $card->update($update);
    logger('card deleted!:' . $_statusLogRow['player_id']);

    $this->collectPlayersTeamChanged($_statusLogRow['player_id'], $_statusLogRow['team_id'], null);
    return  $card->delete();
  }


  public function isPlateCardAleadyExists($_newPlateCardRow)
  {
    // deleted_at 된 카드도 포함한다.
    $samePlateCards = PlateCard::withTrashed()
      ->where(['player_id' => $_newPlateCardRow['player_id']]);

    if (!$samePlateCards->exists()) {
      return null;
    }

    return $samePlateCards;
  }

  public function setCommonWhere(Model $model)
  {
    logger(class_basename($model));
    $result = $model::whereNotIn($model->qualifyColumn('league_id'), [config('constant.LEAGUE_CODE.UCL')])
      ->whereHas('season', function ($query) {
        $query->where('active', YesNo::YES);
      })
      ->when(class_basename($model) === "Squad", function ($query) use ($model) {
        $query->where(
          [
            $model->qualifyColumn('type') => PlayerType::PLAYER,
            $model->qualifyColumn('status') => PlayerStatus::ACTIVE,
            $model->qualifyColumn('active') => YesNo::YES,
          ]
        );
      })
      ->whereIn(
        $model->qualifyColumn('position'),
        [
          PlayerPosition::ATTACKER,
          PlayerPosition::DEFENDER,
          PlayerPosition::GOALKEEPER,
          PlayerPosition::MIDFIELDER,
        ]
      );
    return $result;
  }
}

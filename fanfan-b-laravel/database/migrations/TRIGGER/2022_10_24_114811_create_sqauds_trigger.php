<?php

use App\Enums\Opta\Player\PlayerType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  public function up()
  {
    $query = <<< HEREDOC
CREATE TRIGGER player_to_be_deactivated_role 
  AFTER UPDATE ON `squads` FOR EACH ROW
    BEGIN
      DECLARE changed_type VARCHAR(32);
      SET changed_type = "";
  
      -- 주의: 테이블을 직접 수정한 경우에도 동작되어 status_active_changed_players에 쌓임.
      IF (NEW.league_id != '%s') and (NEW.type = '%s') and (OLD.status != NEW.status or OLD.active != NEW.active) THEN
        IF OLD.status = "active" and OLD.active = "yes" THEN
          IF NEW.status = "retired" THEN
            SET changed_type = "retired";
          ELSEIF NEW.status = "died" THEN
            SET changed_type = "died";
          ELSEIF NEW.status = "active" and NEW.active = "no" THEN
            SET changed_type = "deactivated";
          ELSE
            SET changed_type = "unknown";
          END IF;
        ELSEIF NEW.status = "active" and NEW.active = "yes" THEN
          IF OLD.status = "died" THEN
            SET changed_type = "revived";
          ELSEIF OLD.status = "retired" or (OLD.status = "active" and OLD.active = "no") THEN
            SET changed_type = "comeback";
          ELSE
            SET changed_type = "unknown";
          END IF;
        END IF;
      END IF;
  
      IF changed_type != "" THEN
        INSERT INTO %s.status_active_changed_players 
        (season_id, team_id, player_id, squads_id, old_status, old_active, status, active, changed_type) 
        VALUES (NEW.season_id, NEW.team_id, NEW.player_id, NEW.id, OLD.status, OLD.active, NEW.status, NEW.active, changed_type);
      END IF;
    END
HEREDOC;
    $query = sprintf($query, config('constant.LEAGUE_CODE.UCL'), PlayerType::PLAYER, config('database.connections.log.database'));

    DB::connection('data')->unprepared($query);
  }

  public function down()
  {
    DB::connection('data')->unprepared('DROP TRIGGER IF EXISTS `player_to_be_deactivated_role`');
  }
};

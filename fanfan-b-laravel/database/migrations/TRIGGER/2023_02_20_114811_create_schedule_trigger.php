<?php

use App\Enums\Opta\Schedule\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
  //!TODO (xyz007) schedule -> possible_schedules 로 변경 필요
  public function up()
  {
    $query = <<< HEREDOC
CREATE TRIGGER schedule_status_change_role 
  AFTER UPDATE ON schedules FOR EACH ROW
    BEGIN
      DECLARE c_count INT ;
      SET c_count = (SELECT IFNULL(MAX(IFNULL(index_changed,999))+1, 1) FROM %s.schedule_status_change_logs AS sscl WHERE sscl.schedule_id = OLD.id);
      IF (NEW.status != OLD.status or NEW.started_at != OLD.started_at) THEN
        IF (NEW.status = OLD.status && NEW.started_at != OLD.started_at) THEN
          SET c_count = c_count - 1;
        END IF;

        IF ((NEW.status = '%s' && OLD.status = '%s') and (NEW.started_at != OLD.started_at)) THEN
			    update %s.draft_logs AS dl set dl.origin_started_at = NEW.started_at WHERE dl.schedule_id = NEW.id 
			    and EXISTS (SELECT * FROM (SELECT count(*) FROM %s.draft_logs as ddl WHERE ddl.schedule_id = NEW.id group by ddl.user_plate_card_id, ddl.schedule_id HAVING count(*) = 2) as kb);
        END IF;

        IF ((NEW.status = '%s' && OLD.status = '%s') || (NEW.status = '%s' && OLD.status = '%s') || (NEW.status = '%s' && OLD.status = '%s')) THEN
          SET c_count = c_count - 1;
        END IF;

        INSERT INTO %s.schedule_status_change_logs 
        (index_changed, schedule_id, old_status, new_status, old_winner, new_winner, old_started_at, new_started_at) 
        VALUES (c_count, OLD.id, OLD.status, NEW.status, OLD.winner, NEW.winner, OLD.started_at, NEW.started_at);
        UPDATE %s.game_possible_schedules AS gs SET status = NEW.status WHERE gs.schedule_id = OLD.id;


        IF (NEW.status = '%s' || NEW.status = '%s' || NEW.status = '%s') THEN
          UPDATE %s.game_schedules AS gs SET deleted_at=now() WHERE gs.schedule_id = OLD.id;
        END IF;

        IF (NEW.started_at != OLD.started_at || NEW.status = '%s' || NEW.status = '%s' || NEW.status = '%s') THEN
          UPDATE 
            %s.games, 
            (
              SELECT g.id, MIN(s.started_at) msa, MAX(s.started_at) gsa 
              FROM %s.games AS g 
              JOIN %s.game_schedules AS gs ON g.id = gs.game_id 
              JOIN %s.schedules AS s ON gs.schedule_id = s.id GROUP BY g.id
            ) k 
          SET 
            start_date = k.msa,
            end_date = k.gsa
          WHERE %s.games.id = k.id;
        END IF;

        IF (NEW.status = '%s' || NEW.status = '%s' || NEW.status = '%s' || NEW.status = '%s') THEN
          UPDATE %s.game_possible_schedules AS gs SET ended_at=NEW.ended_at WHERE gs.schedule_id = OLD.id;
        END IF;

      END IF;
    END
HEREDOC;
    $query = sprintf(
      $query,
      config('database.connections.log.database'),
      ScheduleStatus::FIXTURE,
      ScheduleStatus::FIXTURE,
      config('database.connections.log.database'),
      config('database.connections.log.database'),
      ScheduleStatus::PLAYING,
      ScheduleStatus::FIXTURE,
      ScheduleStatus::PLAYED,
      ScheduleStatus::PLAYING,
      ScheduleStatus::AWARDED,
      ScheduleStatus::PLAYED,
      config('database.connections.log.database'),
      config('database.connections.api.database'),
      ScheduleStatus::CANCELLED,
      ScheduleStatus::POSTPONED,
      ScheduleStatus::SUSPENDED,
      config('database.connections.api.database'),
      ScheduleStatus::CANCELLED,
      ScheduleStatus::POSTPONED,
      ScheduleStatus::SUSPENDED,
      config('database.connections.api.database'),
      config('database.connections.api.database'),
      config('database.connections.api.database'),
      config('database.connections.data.database'),
      config('database.connections.api.database'),
      ScheduleStatus::PLAYED,
      ScheduleStatus::CANCELLED,
      ScheduleStatus::POSTPONED,
      ScheduleStatus::SUSPENDED,
      config('database.connections.api.database'),
    );

    DB::connection('data')->unprepared($query);
  }

  public function down()
  {
    DB::connection('data')->unprepared('DROP TRIGGER IF EXISTS schedule_status_change_role');
  }
};

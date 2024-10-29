<?php

namespace App\Libraries\Classes;

use App\Events\AlarmPrivateSocketEvent;
use App\Events\AlarmPublicSocketEvent;
use App\Libraries\Traits\PlayerTrait;
use App\Models\alarm\AlarmLog;
use App\Models\alarm\AlarmTemplate;
use Schema;
use Throwable;
use Exception;

class Alarm
{
  use PlayerTrait;
  protected string $message;
  protected string $id;
  protected ?array $dataset = null;
  protected ?AlarmLog $alarmLog = null;
  protected ?AlarmTemplate $template = null;

  public function __construct(string $_id)
  {
    $this->id = $_id;
  }

  public function setAlarmLog(AlarmLog $_alarmLog)
  {
    $this->alarmLog = $_alarmLog;
    return $this;
  }

  public function params(?array $_param = null): static
  {
    try {
      if (!is_null($this->alarmLog)) {
        $this->template = $this->alarmLog->alarmTemplate;
      } else {
        $this->template = $this->getTemplate();
      }

      if (isset($_param['player'])) {
        $_param['player_name'] = $this->getPlayerNameByPolicy($_param['player']);
        unset($_param['player']);
      }

      preg_match_all('/\{\{([^\}\}]+)\}\}/', $this->template->message['en'], $datasetMatches);
      preg_match_all('/\{\{([^\}\}]+)\}\}/', $this->template->route, $routeMatches);
      $matches = array_unique(array_merge($datasetMatches[1], $routeMatches[1]));

      if (!empty($matches)) {
        foreach ($matches as $replace) {
          if (!array_key_exists($replace, $_param)) {
            throw new Exception('need parameters');
          }
        }
      }
    } catch (Throwable $th) {
      throw $th;
    }

    $this->dataset = $_param;

    return $this;
  }

  public function getConvertedData(): array
  {
    $result = [];
    $messages = [];
    foreach ($this->template->message as $lang => $message) {
      // 초기값
      $messages[$lang] = $this->template->message[$lang];
      if (!is_null($this->dataset)) {
        $messages[$lang] = $this->replace(
          $this->template->message[$lang],
          $this->dataset,
          // 35는 글자수
          $this->template->id === 'community-comment-new' ? 35 : null
        );
      }
    }
    $result['message'] = $messages;

    $result['route'] = null;
    if ($this->template->route) {
      $result['route'] = $this->replace($this->template->route, $this->dataset);
    }
    $result['title'] = $this->template->title;
    if (!is_null($this->dataset)) {
      $result['title'] = $this->replace($this->template->title, $this->dataset);
    }

    $result['highlight'] = [];
    if (isset($this->dataset['player_name'])) {
      $result['highlight'][] = $this->dataset['player_name'];
    }

    return $result;
  }

  public function send(?array $_userIds = null)
  {
    try {
      Schema::connection('log')->disableForeignKeyConstraints();
      if (is_array($_userIds)) {
        foreach ($_userIds as $userId) {
          $alarmLog = new AlarmLog();
          $alarmLog->user_id = $userId;
          $alarmLog->alarm_template_id = $this->id;
          $alarmLog->dataset = $this->dataset;
          $alarmLog->save();
          $alarmLog->makeVisible('created_at');
          $alert = $this->setAlarmLog($alarmLog)->params($alarmLog->dataset);
          unset($alarmLog->alarmTemplate, $alarmLog->dataset);
          $socketArray = array_merge($alarmLog->toArray(), $alert->getConvertedData());
          broadcast(new AlarmPrivateSocketEvent($socketArray, $alarmLog->user));
        }
      } else {
        $alarmLog = new AlarmLog();
        $alarmLog->alarm_template_id = $this->id;
        $alarmLog->dataset = $this->dataset;
        $alarmLog->save();
        $alarmLog->makeVisible('created_at');
        $alert = $this->setAlarmLog($alarmLog)->params($alarmLog->dataset);
        $socketArray = array_merge($alarmLog->toArray(), $alert->getConvertedData());
        unset($alarmLog->alarmTemplate, $alarmLog->dataset, $alarmLog->user_id);
        broadcast(new AlarmPublicSocketEvent($socketArray));
      }
    } catch (Throwable $th) {
      throw $th;
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }

    return true;
  }

  public function getTemplate()
  {
    return AlarmTemplate::select([
      'title',
      'route',
      'message',
    ])
      ->where('id', $this->id)
      ->first();
  }

  private function replace(string $_str, array $_param, $_limit = null): string
  {
    return __pregReplacement('/\{\{([^\}\}]+)\}\}/', $_param, $_str, $_limit);
  }
}

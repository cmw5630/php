<?php

namespace App\Models\log;

use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PredictVoteItem extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function vote()
  {
    return $this->belongsTo(PredictVote::class);
  }

  public function question()
  {
    return $this->belongsTo(PredictVoteQuestion::class, 'predict_vote_question_id');
  }

  // 컬럼명과 같으면 충돌이 나서 언더스코어 처리
  public function option_1()
  {
    return $this->belongsTo(PlateCard::class, 'option1');
  }

  // 컬럼명과 같으면 충돌이 나서 언더스코어 처리
  public function option_2()
  {
    return $this->belongsTo(PlateCard::class, 'option2');
  }

  public function logs()
  {
    return $this->hasMany(PredictVoteLog::class);
  }

  public function oneLog()
  {
    return $this->hasOne(PredictVoteLog::class);
  }
}

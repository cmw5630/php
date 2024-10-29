<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Fantasy\PredictVoteDetailRequest;
use App\Http\Requests\Admin\Fantasy\PredictVoteListRequest;
use App\Http\Requests\Admin\Fantasy\PredictVoteQuestionDeleteRequest;
use App\Http\Requests\Admin\Fantasy\PredictVoteQuestionListRequest;
use App\Http\Requests\Admin\Fantasy\PredictVoteUpdateRequest;
use App\Models\log\PredictVote;
use App\Models\log\PredictVoteItem;
use App\Models\log\PredictVoteQuestion;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PredictVoteController extends Controller
{
  protected int $limit = 20;

  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function questionList()
  {
    $questions = PredictVoteQuestion::get();

    return ReturnData::setData($questions)->send(Response::HTTP_OK);
  }

  public function questionUpdate(PredictVoteQuestionListRequest $request)
  {
    $input = $request->only([
      'id',
      'question',
    ]);

    try {
      if (!is_null($input['id'])) {
        $question = PredictVoteQuestion::find($input['id']);
      } else {
        $question = new PredictVoteQuestion();
      }

      $question->question = $input['question'];
      $question->save();
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function questionDelete(PredictVoteQuestionDeleteRequest $request)
  {
    $input = $request->only([
      'id',
    ]);

    try {
      $question = PredictVoteQuestion::find($input['id']);
      $question->delete();

    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function voteList(PredictVoteListRequest $request)
  {
    $input = $request->only([
      'page',
      'per_page',
    ]);

    $votes = PredictVote::with([
      'items',
      'admin',
    ])
      ->orderByRaw("CASE 
        WHEN now() between started_at and ended_at THEN 0 
        WHEN started_at > NOW() THEN started_at
        ELSE 1
    END")
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $input['page']);

    return ReturnData::setData(__setPaginateData($votes->toArray(), []), $request)->send(Response::HTTP_OK);
  }

  public function voteDetail(PredictVoteDetailRequest $request)
  {
    $filter = $request->only([
      'id'
    ]);

    $result = PredictVote::with([
      'items',
      'admin',
    ])
      ->find($filter['id']);
    dd($result);
  }

  public function voteUpdate(PredictVoteUpdateRequest $request)
  {
    $input = $request->only([
      'id',
      'title',
      'ended_at',
      'item',
    ]);

    DB::beginTransaction();
    try {
      $itemList = [];
      if (is_null($input['id'])) {
        $vote = new PredictVote();
      } else {
        $vote = PredictVote::with('items')->find($input['id']);
        $itemList = array_column($vote->items->toArray(), 'id');
      }

      $vote->title = $input['title'];
      $vote->ended_at = $input['ended_at'];
      $vote->admin_id = $request->user()->id;
      $vote->save();

      // 삭제 처리
      foreach (array_diff($itemList, array_column($input['item'], 'id')) as $item) {
        PredictVoteItem::find($item)->delete();
      }

      foreach ($input['item'] as $item) {
        // 중복 검사
        $exists = PredictVoteItem::where([
          'predict_vote_id' => $vote->id,
          'predict_vote_question_id' => $item['question'],
        ])
          ->exists();

        if ($exists) {
          throw new Exception('duplicate question.');
        }

        if (isset($item['id'])) {
          $voteItem = PredictVoteItem::find($item['id']);
        } else {
          $voteItem = new PredictVoteItem();
        }

        $voteItem->predict_vote_id = $vote->id;
        $voteItem->predict_vote_question_id = $item['question'];
        $voteItem->option1 = $item['option1'];
        $voteItem->option2 = $item['option2'];
        $voteItem->save();
      }

      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }
}

<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Http\Controllers\API\v1\CommunityController as ApiCommunity;
use App\Http\Requests\Admin\Community\PostStoreRequest;
use App\Http\Requests\Admin\Community\RestrictRequest;
use App\Http\Requests\Api\Community\CommentDeleteRequest;
use App\Http\Requests\Admin\Community\CommentListRequest;
use App\Http\Requests\Api\Community\ImageUploadRequest;
use App\Http\Requests\Admin\Community\PostDetailRequest;
use App\Http\Requests\Api\Community\PostListRequest;
use App\Models\community\Comment;
use App\Models\community\Post;
use App\Models\community\PostAttach;
use ReturnData;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CommunityController extends ApiCommunity
{
  protected int $limit = 20;

  public function listAdmin(PostListRequest $request)
  {
    return $this->list($request, true);
  }

  public function storeAdmin(PostStoreRequest $request)
  {
    $input = $request->only([
      'post_id',
      // 'sort_type',
      'board_id',
      'category_id',
      'title',
      'content',
    ]);

    try {
      if (is_null($input['post_id'])) {
        $post = new Post;
        $post->board_id = $input['board_id'];
      } else {
        $post = Post::find($input['post_id']);
      }

      // $post->sort_type = $input['sort_type'];
      $post->board_id = $input['category_id'];
      $post->title = $input['title'];
      $post->content = $input['content'];
      $post->admin_id = $request->user('admin')->id;

      $post->save();
      $socketData = [
        'template_id' => 'community-notice-new',
        'dataset' => [
          'post_id' => $post->id,
        ],
      ];

      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send(null);
      return ReturnData::setData(['post_id' => $post->id])->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function updateAdmin(PostStoreRequest $request)
  {
    return $this->storeAdmin($request);
  }

  public function detailAdmin(PostDetailRequest $request)
  {
    return $this->detail($request, true);
  }

  public function deleteAdmin(PostDetailRequest $request)
  {
    $filter = $request->only([
      'post_id',
    ]);

    try {
      Post::destroy($filter['post_id']);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function restrict(RestrictRequest $request)
  {
    $input = $request->only([
      'type',
      'post_id',
      'comment_id',
      'reason',
    ]);

    if (is_null($input['comment_id'])) {
      $target = Post::find($input['post_id']);
    } else {
      $target = Comment::find($input['comment_id']);
    }

    $target->status = $input['type'];
    $target->restricted_reason = $input['reason'];
    $target->restricted_admin_id = $request->user()->id;
    $target->save();

    return ReturnData::send(Response::HTTP_OK);
  }

  public function commentListAdmin(CommentListRequest $request)
  {
    return $this->commentList($request, true);
  }

  public function commentReplyListAdmin(CommentListRequest $request)
  {
    return $this->commentReplyList($request, true);
  }

  public function commentDelete(CommentDeleteRequest $request)
  {
    $filter = $request->only([
      'comment_id',
    ]);

    try {
      Comment::destroy($filter['comment_id']);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function imageUpload(ImageUploadRequest $request)
  {
    $imageFile = $request->file('image');
    $storagePath = 'community/attach_files';
    try {
      $path = Storage::putFileAs(
        $storagePath,
        $imageFile,
        'a_' . $request->user()->id . '_' . Str::substr($imageFile->hashName(), 0, 15) . '.' . $imageFile->extension()
      );

      $postAttach = new PostAttach;
      $postAttach->admin_id = $request->user()->id;
      $postAttach->real_name = $imageFile->getClientOriginalName();
      $postAttach->file_name = $path;
      $postAttach->save();

      $data['file_path'] = $path;
    } catch (Throwable $th) {
      // 저장된 file 삭제
      if (isset($fileName)) {
        Storage::disk()->delete($storagePath . '/' . $fileName);
      }
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::setData($data)->send(Response::HTTP_OK);
  }
}

<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\CommunityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Community\CommentDeleteRequest;
use App\Http\Requests\Api\Community\CommentListRequest;
use App\Http\Requests\Api\Community\CommentStoreRequest;
use App\Http\Requests\Api\Community\ImageUploadRequest;
use App\Http\Requests\Api\Community\PostDetailRequest;
use App\Http\Requests\Api\Community\PostListRequest;
use App\Http\Requests\Api\Community\PostStoreRequest;
use App\Models\community\Board;
use App\Models\community\Comment;
use App\Models\community\Post;
use App\Models\community\PostAttach;
use Arr;
use DB;
use ReturnData;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CommunityController extends Controller
{
  protected int $limit = 20;
  public function list(PostListRequest $request, bool $isAdmin = false)
  {
    $filter = $request->only([
      // 'sort_type',
      'search_type',
      'q',
      'page',
      'per_page',
      'list_type',
      'board_id',
      'category_id'
    ]);

    if (!$isAdmin) {
      unset($filter['list_type']);
    }

    $this->limit = $filter['per_page'];

    $noticeId = Board::where('name', 'notice')->value('id');
    $notice = [];
    // if ($filter['sort_type'] !== PostSortType::NOTICE && is_null($filter['q'])) {
    if ($filter['board_id'] != $noticeId && is_null($filter['q'])) {
      $notice = Post::withCount(['comments as comment_count' => function ($query) {
        $query->where('status', '!=', CommunityStatus::DELETE);
      }])
        // ->where('sort_type', PostSortType::NOTICE)
        ->where('board_id', $noticeId)
        ->when(
          isset($filter['list_type']) && $filter['list_type'] === CommunityStatus::DELETE,
          function ($query) {
            $query->where('status', CommunityStatus::DELETE);
          },
          function ($query) {
            $query->where('status', '!=', CommunityStatus::DELETE);
          }
        )
        ->latest()
        ->limit(3)
        ->get()
        ->map(function ($item) {
          $item->has_images = (bool) preg_match(
            "/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/",
            $item->content
          );
          $item->has_links = (bool) preg_match(
            "/<a[^>]*href=[\"']?([^>\"']+)[\"']?[^>]*>/",
            $item->content
          );
          unset($item->content);

          return $item;
        })
        ->toArray();
    }

    $list = tap(
      Post::with([
        'user' => function ($query) use ($isAdmin) {
          $query
            // ->when($isAdmin, function ($whenAdmin) {
            //   $whenAdmin->withoutGlobalScope('excludeWithdraw');
            // })
            ->select(['id', 'name', 'status']);
        },
      ])
        ->withCount(['comments as comment_count' => function ($query) {
          $query->where('status', '!=', CommunityStatus::DELETE);
        }])
        ->when(
          isset($filter['list_type']) && $filter['list_type'] === CommunityStatus::DELETE,
          function ($query) {
            $query->where('status', CommunityStatus::DELETE);
          },
          function ($query) {
            $query->where('status', '!=', CommunityStatus::DELETE);
          }
        )
        ->when($filter['q'], function ($whenQuery, $q) use ($filter) {
          switch ($filter['search_type']) {
            case 'name':
              $whenQuery->whereHas('user', function ($query) use ($q) {
                $query->where('name', $q);
              });
              break;
            default:
              if ($filter['search_type'] === 'title_content') {
                $filter['search_type'] = ['title', 'content'];
              } else {
                $filter['search_type'] = [$filter['search_type']];
              }
              $whenQuery->whereLike($filter['search_type'], $q);
          }
        })
        ->where('board_id', $filter['board_id'])
        // ->when($filter['sort_type'], function ($whenSortType, $sortType) {
        //   $whenSortType->where('sort_type', $sortType);
        // }, function ($whenSortType) {
        //   $whenSortType->where('sort_type', '!=', PostSortType::NOTICE);
        // })
        ->when($filter['category_id'], function ($whenCategory, $categoryId) {
          $whenCategory->where('board_category_id', $categoryId);
        })
        ->latest()
        ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )->map(function ($info) {
      // 제목 내용 숨김처리
      $info->makeHide();
      $info->has_images = (bool) preg_match(
        "/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/",
        $info->content
      );
      $info->has_links = (bool) preg_match(
        "/<a[^>]*href=[\"']?([^>\"']+)[\"']?[^>]*>/",
        $info->content
      );
      unset($info->content);

      return $info;
    });
    $list = __setPaginateData($list->toArray(), []);

    return ReturnData::setData(compact('notice', 'list'), $request)->send(Response::HTTP_OK);
  }

  public function store(PostStoreRequest $request)
  {
    $input = $request->only([
      'board_id',
      'category_id',
      'post_id',
      // 'sort_type',
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
      $post->board_category_id = $input['category_id'];
      $post->title = $input['title'];
      $post->content = $input['content'];
      $post->user_id = $request->user()->id;

      $post->save();

      return ReturnData::setData(['post_id' => $post->id])->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function update(PostStoreRequest $request)
  {
    return $this->store($request);
  }

  public function detail(PostDetailRequest $request, bool $isAdmin = false)
  {
    $filter = $request->only([
      'post_id',
    ]);

    $noticeId = Board::where('name', 'notice')->value('id');
    $post = Post::with([
      'user' => function ($query) use ($isAdmin) {
        $query->when($isAdmin, function ($whenAdmin) {
          $whenAdmin->withoutGlobalScope('excludeWithdraw');
        })
          ->select(['id', 'name', 'status']);
      },
      'user.userMeta',
    ])->withCount(['comments as comment_count' => function ($query) {
      $query->where('status', '!=', 'delete');
    }])
      ->find($filter['post_id']);

    // 조회수 증가
    $post->view_count++;
    $post->save();

    $post['previous_post_id'] = Post::where([
      ['id', '<', $post->id],
      ['status', CommunityStatus::NORMAL],
      // ['sort_type', '!=', PostSortType::NOTICE]
      ['board_id', '!=', $noticeId]
    ])->max('id');
    $post['next_post_id'] = Post::where([
      ['id', '>', $post->id],
      ['status', CommunityStatus::NORMAL],
      // ['sort_type', '!=', PostSortType::NOTICE]
      ['board_id', '!=', $noticeId]
    ])->min('id');

    return ReturnData::setData($post)->send(Response::HTTP_OK);
  }

  public function delete(PostDetailRequest $request)
  {
    $filter = $request->only([
      'post_id',
    ]);

    try {
      $post = Post::find($filter['post_id']);
      $post->status = CommunityStatus::DELETE;
      $post->save();
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function commentList(CommentListRequest $request, bool $isAdmin = false)
  {
    $filter = $request->only([
      'post_id',
      'page',
      'per_page',
      'more_list'
    ]);

    $this->limit = $filter['per_page'];
    $moreList = json_decode($filter['more_list'], true);
    $list = tap(
      Comment::with([
        'user' => function ($query) use ($isAdmin) {
          $query->when($isAdmin, function ($whenAdmin) {
            $whenAdmin->withoutGlobalScope('excludeWithdraw');
          })
            ->select(['id', 'name', 'status']);
        },
        'user.userMeta',
        'replies' => function ($query) {
          return $query->where('status', '!=', CommunityStatus::DELETE)->oldest();
        },
        'replies.user' => function ($query) use ($isAdmin) {
          $query->when($isAdmin, function ($whenAdmin) {
            $whenAdmin->withoutGlobalScope('excludeWithdraw');
          })
            ->select(['id', 'name', 'status']);
        },
        'replies.user.userMeta',
        'replies.mentionedUser' => function ($query) use ($isAdmin) {
          $query->when($isAdmin, function ($whenAdmin) {
            $whenAdmin->withoutGlobalScope('excludeWithdraw');
          })
            ->select(['id', 'name', 'status']);
        },
      ])
        ->withCount(['replies as reply_count' => function ($query) {
          $query->where('status', '!=', 'delete');
        }])
        ->where('post_id', $filter['post_id'])
        ->whereNull('parent_comment_id')
        ->where(function ($query) {
          return $query->where('status', '!=', CommunityStatus::DELETE)
            ->orWhereHas('replies', function ($reply) {
              $reply->where('status', '!=', CommunityStatus::DELETE);
            });
        })
        // ->where('status', '!=', CommunityStatus::DELETE)
        ->latest()
        ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )->map(function ($item) use ($moreList) {
      $take = 10;
      if (!is_null($moreList)) {
        foreach ($moreList as $more) {
          if ($more['comment_id'] === $item->id) {
            $take += $take * $more['more_count'];
          }
        }
      }
      $item->setRelation('replies', $item->replies->take($take));
      return $item;
    });

    $commentCount = Comment::where([
      ['post_id', $filter['post_id']],
      ['status', '!=', CommunityStatus::DELETE]
    ])
      ->count();

    return ReturnData::setData(__setPaginateData($list->toArray(), ['total' => $commentCount]), $request)->send(Response::HTTP_OK);
  }

  public function commentReplyList(CommentListRequest $request, bool $isAdmin)
  {
    $filter = $request->only([
      'post_id',
      'comment_id',
      'offset',
      'per_page',
    ]);

    $list = Comment::with([
      'user' => function ($query) use ($isAdmin) {
        $query->when($isAdmin, function ($whenAdmin) {
          $whenAdmin->withoutGlobalScope('excludeWithdraw');
        })
          ->select(['id', 'name', 'status']);
      },
      'user.userMeta',
      'mentionedUser' => function ($query) use ($isAdmin) {
        $query->when($isAdmin, function ($whenAdmin) {
          $whenAdmin->withoutGlobalScope('excludeWithdraw');
        })
          ->select(['id', 'name', 'status']);
      },
    ])
      ->where('post_id', $filter['post_id'])
      ->where('status', '!=', CommunityStatus::DELETE)
      ->when($filter['comment_id'], function ($query, $commentId) {
        $query->where('parent_comment_id', $commentId);
      })
      ->when($filter['offset'], function ($query, $offset) {
        $query->where('id', '>', $offset);
      })
      ->oldest()
      ->limit(10)
      ->get();

    $hasMore = Comment::where('post_id', $filter['post_id'])
      ->when($filter['comment_id'], function ($query, $commentId) {
        $query->where('parent_comment_id', $commentId);
      })
      ->whereNot('status', CommunityStatus::DELETE)
      ->where('id', '>', $list->last()->id)
      ->oldest()
      ->exists();

    return ReturnData::setData(['has_more' => $hasMore, 'list' => $list], $request)->send(Response::HTTP_OK);
  }

  public function commentStore(CommentStoreRequest $request)
  {
    $input = $request->only([
      'post_id',
      'parent_id',
      'comment_id',
      'mentioned_user_id',
      'content',
      'attach_images',
    ]);

    DB::beginTransaction();

    try {
      if (!isset($input['comment_id'])) {
        $comment = new Comment;
        $comment->user_id = $request->user()->id;
        $comment->post_id = $input['post_id'];
        if (isset($input['parent_id'])) {
          $comment->parent_comment_id = $input['parent_id'];
        }
      } else {
        $comment = Comment::find($input['comment_id']);
      }

      if (Arr::exists($input, 'mentioned_user_id')) {
        $comment->mentioned_user_id = $input['mentioned_user_id'];
      }

      $comment->content = $input['content'];
      $comment->attach_images = $input['attach_images'];
      $comment->save();

      $post = $comment->post;
      // 새 댓글 작성 때만
      if (!isset($input['comment_id'])) {
        // 답변, 언급 우선 알림
        if (isset($input['parent_id'])) {
          $socketData = [
            'template_id' => 'community-comment-reply',
            'target_user_id' => $request->user()->id,
            'dataset' => [
              'user_name' => $request->user()->name,
              'post_id' => $input['post_id'],
            ],
          ];
          $alarm = app('alarm', ['id' => $socketData['template_id']]);
          $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);
        }

        if (!isset($input['parent_id']) && $post->user_id !== $comment->parent?->user_id) {
          $socketData = [
            'template_id' => 'community-comment-new',
            'target_user_id' => $post->user_id,
            'dataset' => [
              'title' => $post->title,
              'comment_cnt' => $post->comments->count(),
              'post_id' => $input['post_id'],
            ],
          ];

          $alarm = app('alarm', ['id' => $socketData['template_id']]);
          $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);
        }
      }

      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function commentDelete(CommentDeleteRequest $request)
  {
    $filter = $request->only([
      'comment_id',
    ]);

    try {
      $comment = Comment::find($filter['comment_id']);
      $comment->status = CommunityStatus::DELETE;
      $comment->save();
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
        $request->user()->id . '_' . Str::substr($imageFile->hashName(), 0, 15) . '.' . $imageFile->extension()
      );

      $postAttach = new PostAttach;
      $postAttach->user_id = $request->user()->id;
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

  public function categoryList()
  {
    try {
      $data = Board::with('boardCategory:id,board_id,name')
        ->select('id', 'name')
        ->get()
        ->toArray();
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($data)->send(Response::HTTP_OK);
  }
}

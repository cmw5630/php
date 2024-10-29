<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Enums\ErrorDefine;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Op\BannerDeleteRequest;
use App\Http\Requests\Admin\Op\BannerListRequest;
use App\Http\Requests\Admin\Op\BlockIpRequest;
use App\Http\Requests\BannerUpdateRequest;
use App\Models\Banner;
use App\Models\BlockedIp;
use App\Models\user\User;
use DB;
use ReturnData;
use Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OpController extends Controller
{
  protected int $limit = 20;

  public function __construct()
  {
  }

  public function dashboard()
  {
    $subDay = 0;
    while(true) {
      $user['new'][now()->subDays($subDay++)->toDateString()] = 0;
      if ($subDay >= 7) {
        break;
      }
    }

    User::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as date, count(*) as cnt")
      ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m-%d')")
      ->latest('date')
      ->whereBetween('created_at', [now()->subDays(7)->startOfDay(), now()])
      ->get()
      ->map(function ($item) use (&$user) {
        $user['new'][$item->date] = $item->cnt;
      });

    $user['total'] = User::where('status', UserStatus::NORMAL)->get()->count();


    return $user;
  }

  public function bannerList(BannerListRequest $request)
  {
    $filter = $request->only([
      'banner_id',
      'platform',
      'location',
      'image_path',
      'page',
      'per_page',
    ]);

    $this->limit = $filter['per_page'];

    $codeList = __getCodeInfo('B01');

    $list = tap(Banner::with('admin:id,name,nickname')
    ->when($filter['platform'], function ($query, $platform) {
      $query->where('platform', $platform);
    })
      ->when($filter['location'], function ($query, $location) {
        $query->where('location', $location);
      })
      ->selectRaw("*, 
      (case when started_at > now() then 'scheduled'
      when ended_at < now() then 'expired'
      else 'progressing' end) as status
      ")
      ->orderByRaw("field(status, 'progressed', 'scheduled', 'expired')")
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )
      ->map(function ($item) use ($codeList) {
        $item->location = $codeList[$item->location];
        $item->makeVisible('created_at');
        return $item;
      })
    ->toArray();

    $options = [];
    foreach (__getCodeInfo('B01') as $item) {
      if ($item['code'] < 100) {
        $options['pc'][] = $item;
      } else {
        $options['mobile'][] = $item;
      }
    }

    return ReturnData::setData(__setPaginateData($list, [], compact('options')), $request)->send(Response::HTTP_OK);
  }

  public function bannerUpdate(BannerUpdateRequest $request)
  {
    $input = $request->only([
      'banner_id',
      'link_url',
      'platform',
      'location',
      'order',
      'started_at',
      'ended_at',
    ]);

    try {
      if (is_null($input['banner_id'])) {
        $banner = new Banner();
      } else {
        $banner = Banner::find($input['banner_id']);
        $oldPath = $banner->image_path;
      }

      $banner->admin_id = $request->user()->id;
      $banner->platform = $input['platform'];
      $banner->location = $input['location'];
      $banner->link_url = $input['link_url'];
      if (!is_null($input['order'])) {
        $banner->order_no = $input['order'];
      }
      $banner->started_at = $input['started_at'];
      $banner->ended_at = $input['ended_at'];

      $file = $request->file('image');
      $storage = Storage::disk();
      $path = $storage->putFileAs('banner', $file, $file->hashName());
      $banner->file_name = $file->getClientOriginalName();
      $banner->image_path = $path;
      $banner->save();

      if (isset($oldPath)) {
        Storage::delete($oldPath);
      }
    } catch (Throwable $th) {
      if (isset($storage) && isset($path)) {
        $storage->delete($path);
      }

      return ReturnData::setError([
        ErrorDefine::INTERNAL_SERVER_ERROR,
        $th->getMessage()
      ])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function bannerDelete(BannerDeleteRequest $request)
  {
    $input = $request->only('banner_id');

    DB::beginTransaction();
    
    try {
      $banner = Banner::find($input['banner_id']);
      $banner->admin_id = $request->user()->id;
      $banner->save();
      Storage::delete($banner->image_path);
      $banner->delete();
      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      return ReturnData::setError([
        ErrorDefine::INTERNAL_SERVER_ERROR,
        $th->getMessage()
      ])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function blockIp(BlockIpRequest $request)
  {
    $input = $request->only('ip_address');

    $blockedIp = new BlockedIp;
    $blockedIp->admin_id = $request->user()->id;
    $blockedIp->ip_address = $input['ip_address'];
    $blockedIp->save();

    return ReturnData::send(Response::HTTP_OK);
  }

  public function blockIpDelete(BlockIpRequest $request)
  {
    $input = $request->only('blocked_ip_id');

    $blockedIp = BlockedIp::find($input['blocked_ip_id']);
    $blockedIp->admin_id = $request->user()->id;
    $blockedIp->save();
    $blockedIp->delete();

    return ReturnData::send(Response::HTTP_OK);
  }
}

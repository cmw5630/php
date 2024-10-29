<?php

namespace App\Http\Controllers\API\v1\User;

use App\Enums\ErrorDefine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SocialAuthCodeRequest;
use App\Http\Requests\Api\Auth\SocialRequest;
use App\Models\user\User;
use App\Services\User\UserService;
use Google\Client;
use Laravel\Socialite\Facades\Socialite;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use App\Models\user\LinkedSocialAccount;
use DB;


class SocialController extends Controller
{
  public function socialAuthCode(string $provider, SocialAuthCodeRequest $request)
  {
    if ($provider === 'google') {
      return $this->googleAuthCode($request);
    }
    return $this->getAuthToken($provider, $request);
  }

  private function googleAuthCode($request)
  {
    $config = config('services.google');
    $input = $request->only([
      'code',
      'redirect'
    ]);

    $client = new Client();
    $client->setAuthConfig($config);

    $client->setRedirectUri($input['redirect'] ? urldecode($input['redirect']) : $config['redirect']);
    $token = $client->fetchAccessTokenWithAuthCode($input['code']);
    if (isset($token['error'])) {
      return ReturnData::setError([ErrorDefine::BAD_REQUEST, $token['error_description']])->send(Response::HTTP_BAD_REQUEST);
    }

    return ReturnData::setData($token)->send(200);
  }

  public function getAuthToken(string $provider, $request)
  {
    $input = $request->only([
      'code',
      'state',
    ]);

    $socialiteProvider = Socialite::driver($provider)
      ->stateless()
      ->with(['state' => $input['state']]);

    $token = $socialiteProvider->getAccessTokenResponse($input['code']);

    if (isset($token['error'])) {
      return ReturnData::setError([ErrorDefine::BAD_REQUEST, $token['error_description']])->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($token)->send(200);
  }

  public function socialConfirm(SocialRequest $request): object
  {
    $input = $request->only([
      'nation',
      'name',
      'provider',
      'access_token',
      'grant_type',
      'favorite_team',
      'referral_code',
      'optional_agree',
    ]);

    DB::beginTransaction();
    try {
      $providerUser = Socialite::driver($input['provider'])->userFromToken($input['access_token']);

      $linkedSocialAccount = LinkedSocialAccount::where('provider_name', $input['provider'])
        ->where('provider_id', $providerUser->getId())
        ->first();

      if ($linkedSocialAccount) {

        $userData = $linkedSocialAccount->user;

      } else {
        $user = null;

        if ($email = $providerUser->getEmail()) {
          $user = User::where('email', $email)
            ->whereHas('linkedSocialAccounts', function ($query) use($providerUser){
              $query->where('provider_id', $providerUser->getId());
            })
            ->first();
        }

        if (!$user) {

          $user = new User();
          $user->email = $providerUser->getEmail();
          $user->name = $input['name'];
          $user->nation = $input['nation'];
          $user->save();

          $userService = new UserService($user);
          $userService->setUserMetaInfo($user->id, [
            'favorite_team' => $input['favorite_team'],
            'user_referral_code' => $input['referral_code'] ?? null,
            'optional_agree' => $input['optional_agree'],
          ]);
        }

        $user->linkedSocialAccounts()->create([
          'provider_id' => $providerUser->getId(),
          'provider_name' => $input['provider'],
        ]);
        $userData = $user;
      }

      $userData['grant_type'] = $input['grant_type'];
      $userData['access_token'] = $input['access_token'];
      $userData['provider'] = $input['provider'];

    } catch (Exception $e) {
      DB::rollback();
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, __('common.internal_server_error')])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    DB::commit();
    return ReturnData::setData($userData)->send(Response::HTTP_OK);
  }
}

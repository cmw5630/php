<?php

namespace App\Services\User;

use App\Models\user\LinkedSocialAccount;
use App\Models\user\User;
use Laravel\Socialite\Two\User as ProviderUser;

interface SocialAccountsServiceInterface
{
  public function findOrCreate(ProviderUser $providerUser, string $provider): ?User;
}

class SocialAccountsService implements SocialAccountsServiceInterface
{
  /**
   * Find or create user instance by provider user instance and provider name.
   *
   * @param ProviderUser $providerUser
   * @param string $provider
   *
   * @return User
   */
  public function findOrCreate(ProviderUser $providerUser, string $provider): ?User
  {
    $linkedSocialAccount = LinkedSocialAccount::where('provider_name', $provider)
      ->where('provider_id', $providerUser->getId())
      ->first();

    return $linkedSocialAccount?->user;

  }
}

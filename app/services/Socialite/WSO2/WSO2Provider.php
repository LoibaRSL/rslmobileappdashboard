<?php

namespace App\Services\Socialite\WSO2;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class WSO2Provider extends AbstractProvider implements ProviderInterface
{
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.wso2.authorization_url'), $state);
    }

    protected function getTokenUrl()
    {
        return config('services.wso2.token_url');
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(config('services.wso2.userinfo_url'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'verify' => 'C:\xampp_lite_8_5\apps\apache\conf\ssl\star_rsl_org_ls.crt', // Disable SSL verification
        ]);

        \Log::info('User info response: ' . $response->getBody());

        return json_decode($response->getBody(), true);;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'name' => $user['name'] ?? null,
            'username' => $user['username'] ?? null,
            'email' => $user['email'] ?? null,
            'department' => $user['department'] ?? null,
            'division' => $user['division'] ?? null,
            'position' => $user['position'] ?? null,
        ]);
    }




    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'scope' => 'openid email phone profile', // Add your required scopes
        ]);
    }

    protected function getCodeFields($state = null)
    {
        return array_merge(parent::getCodeFields($state), [
            'scope' => 'openid email profile', // Add your required scopes
        ]);
    }
}

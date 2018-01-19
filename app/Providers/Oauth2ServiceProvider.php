<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use Wx\Grant\WeChatOpenIdGrant;

class Oauth2ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $oauthServer = $this->app->make(AuthorizationServer::class);
        $oauthServer->enableGrantType($this->makeWechatOpenIdGrant(),Passport::tokensExpireIn());
        $this->app->instance(AuthorizationServer::class,$oauthServer);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    protected function makeWechatOpenIdGrant(){
        $grant = new WeChatOpenIdGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        return $grant;
    }
}

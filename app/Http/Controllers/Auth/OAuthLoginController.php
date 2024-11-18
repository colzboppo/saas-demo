<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class OAuthLoginController extends Controller
{
    /**
     * handles our login / redirect to OAuth provider
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * handles our authorized callback OAuth provider
     */
    public function handleAuthorization(string $provider)
    {
        /** @var Laravel\Socialite\Two\User $oauthUser */
        $socialite = Socialite::driver($provider);
        $oauthUser = $socialite->stateless()->user();
        
        $user = User::firstOrCreate(
            ['email' => $oauthUser->getEmail()],
            [
                'name' => $oauthUser->getName(),
                'email' => $oauthUser->getEmail(),
                'password' => bcrypt($oauthUser->token),
                'remember_token' => $oauthUser->refreshToken,
                'oauth_provider' => $provider,
                'oauth_provider_id' => $oauthUser->getId()
            ]
        );

        Auth::login($user);

        Session::regenerate();

        return redirect()->intended('dashboard');
    }
}

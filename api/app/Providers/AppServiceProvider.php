<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SIM_ASN\Laravel\Facades\UserClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SIM-ASN token refresh handler
        // When UserClient refreshes its access token, persist the new token to the database.
        UserClient::onRefreshToken(function (\SIM_ASN\Models\AccessToken $newToken) {
            // Only update if this request has an authenticated user with a stored SIM-ASN token
            if (auth()->check() && auth()->user()->sim_asn_token) {
                auth()->user()->forceFill([
                    'sim_asn_token' => [
                        'access_token' => $newToken->access_token,
                        'refresh_token' => $newToken->refresh_token ?? auth()->user()->sim_asn_token['refresh_token'] ?? null,
                        'expires_at' => $newToken->expires_at ?? null,
                    ],
                ])->save();
            }
        });
    }
}

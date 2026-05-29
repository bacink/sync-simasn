<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SIM_ASN\Laravel\Facades\OauthClient;

class SimAsnCallbackController extends Controller
{
    private const TOKEN_NAME = 'sim-asn-token';

    /**
     * Redirect browser to SIM-ASN authorization page.
     */
    public function initiate(Request $request): RedirectResponse
    {
        $request->session()->put('oauth_return_to', $request->get('return_to', '/dashboard'));

        return OauthClient::requestCode('login');
    }

    /**
     * Handle OAuth callback from SIM-ASN.
     *
     * Flow:
     *  1. OauthClient::handleCallback() receives SIM-ASN's authorization code
     *  2. Our closure receives (SimAsnUser, AccessToken) from the SDK
     *  3. Find/create local user, save SIM-ASN token, issue Sanctum token
     *  4. Redirect to frontend with Sanctum token in URL
     */
    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        try {
            $result = OauthClient::handleCallback($request, function ($simAsnUser, $accessToken) use ($returnTo) {
                $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

                if (! $user) {
                    // User not found — encode SIM-ASN data for registration
                    $payload = base64_encode(json_encode([
                        'sim_asn_user_id' => $simAsnUser->id,
                        'name' => $simAsnUser->name ?? null,
                        'email' => $simAsnUser->email ?? null,
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ]));

                    Log::info('SIM-ASN user not found, redirecting to registration', [
                        'sim_asn_user_id' => $simAsnUser->id,
                    ]);

                    return redirect()->to($returnTo.'?oauth_register='.urlencode($payload));
                }

                // Existing user — update SIM-ASN token and create Sanctum token
                $sanctumToken = DB::transaction(function () use ($user, $accessToken) {
                    $user->sim_asn_token = [
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ];
                    $user->save();

                    // Schedule old token deletion after commit (prevent lockout if create fails)
                    DB::afterCommit(function () use ($user) {
                        $user->tokens()->delete();
                    });

                    return $user->createToken(self::TOKEN_NAME)->plainTextToken;
                });

                Log::info('SIM-ASN login successful', [
                    'user_id' => $user->id,
                    'sim_asn_user_id' => $simAsnUser->id,
                ]);

                return redirect()->to($returnTo.'?access_token='.urlencode($sanctumToken));
            });

            // handleCallback returns RedirectResponse — pass through
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            // Unexpected result type
            Log::error('SIM-ASN callback: unexpected result', ['type' => gettype($result)]);

            return redirect()->to($returnTo.'?error='.urlencode('oauth_callback_failed'));

        } catch (\Throwable $e) {
            Log::error('SIM-ASN OAuth callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to($returnTo.'?error='.urlencode('oauth_callback_failed'));
        }
    }
}

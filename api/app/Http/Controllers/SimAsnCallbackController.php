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
     * OauthClient::handleCallback() calls our closure with user+token data,
     * then returns a RedirectResponse pointing to SIM-ASN's callback URL
     * (e.g. ?access_token=<base64-encoded-json> or ?error=<message>).
     * We parse that redirect URL, extract the token or error, and pass the
     * Sanctum token back to the frontend via the return_to redirect.
     */
    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        try {
            // handleCallback() expects a closure: fn(User $user, AccessToken $token) -> RedirectResponse.
            // It returns a RedirectResponse with ?access_token=<base64> or ?error=.
            $result = OauthClient::handleCallback($request, function ($simAsnUser, $accessToken) use ($returnTo) {
                // Check if user exists locally
                $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

                if (!$user) {
                    // Encode SIM-ASN user data + token for frontend registration pre-fill.
                    $payload = base64_encode(json_encode([
                        'user' => $simAsnUser,
                        'token' => $accessToken,
                    ]));
                    return redirect()->to($returnTo . '?oauth_register=' . urlencode($payload));
                }

                // Existing user — save SIM-ASN token and create Sanctum token.
                $sanctumToken = DB::transaction(function () use ($user, $accessToken) {
                    $user->sim_asn_token = [
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ];
                    $user->save();

                    // Revoke all existing tokens so we only have one active session.
                    $user->tokens()->delete();

                    return $user->createToken(self::TOKEN_NAME)->plainTextToken;
                });

                Log::info('SIM-ASN login successful', [
                    'user_id' => $user->id,
                    'sim_asn_user_id' => $simAsnUser->id,
                ]);

                return redirect()->to($returnTo . '?access_token=' . urlencode($sanctumToken));
            });

            // The SDK returns a RedirectResponse to SIM-ASN's callback URL.
            // Parse it to extract the access_token or error query param.
            if ($result instanceof RedirectResponse) {
                $targetUrl = $result->getTargetUrl();
                $parsed = parse_url($targetUrl, PHP_URL_QUERY);
                parse_str($parsed ?? '', $query);

                if (!empty($query['access_token'])) {
                    // access_token in SIM-ASN callback URL = the redirect we built above.
                    // Extract the actual token value (it's URL-encoded base64 or direct string).
                    // Our redirect URL format: ?access_token=<urlencoded-sanctum-token> or
                    // ?oauth_register=<urlencoded-base64>.
                    // Check for our direct token first.
                    if (str_contains($targetUrl, 'access_token=')) {
                        return redirect()->to($targetUrl); // passthrough as-is
                    }
                }

                if (!empty($query['error'])) {
                    return redirect()->to($returnTo . '?error=' . urlencode($query['error']));
                }

                // Fallback: passthrough the redirect as-is.
                return $result;
            }

            // Should not reach here — handleCallback always returns RedirectResponse.
            Log::error('SIM-ASN callback: unexpected non-redirect result', ['type' => gettype($result)]);
            return redirect()->to($returnTo . '?error=' . urlencode('oauth_callback_failed'));

        } catch (\Throwable $e) {
            Log::error('SIM-ASN OAuth callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't leak internal error messages — map to user-friendly codes.
            return redirect()->to($returnTo . '?error=' . urlencode('oauth_callback_failed'));
        }
    }
}

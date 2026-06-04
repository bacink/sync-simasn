<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SIM_ASN\Laravel\Facades\OauthClient;
use SIM_ASN\Models\AccessToken;
use SIM_ASN\Models\User as SimAsnUser;

class SimAsnCallbackController extends Controller
{
    /**
     * Redirect browser to SIM-ASN authorization page.
     */
    public function initiate(Request $request): RedirectResponse
    {
        // Store return_to URL in session so callback knows where to redirect after login
        $returnTo = $request->get('return_to', $request->session()->get('oauth_return_to', '/dashboard'));
        $request->session()->put('oauth_return_to', $returnTo);

        return OauthClient::requestCode('login');
    }

    /**
     * Handle OAuth callback from SIM-ASN.
     *
     * Flow:
     *  1. Exchange code for SIM-ASN token via SDK
     *  2. Find or register the user
     *  3. Save SIM-ASN token to user's sim_asn_token field
     *  4. Create a Sanctum token
     *  5. Redirect to frontend with Sanctum token in query string
     */
    public function callback(Request $request): RedirectResponse
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        try {
            $result = OauthClient::handleCallback($request, function (SimAsnUser $simAsnUser, AccessToken $accessToken) use ($returnTo) {
                // Find existing user by sim_asn_user_id
                $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

                if (! $user) {
                    // User not found — redirect to registration page with SIM-ASN user ID
                    // We pass the SIM-ASN user ID as an opaque token so the frontend can
                    // submit it to the registration endpoint.
                    $encoded = base64_encode(json_encode([
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

                    return redirect()->away($returnTo.'?oauth_register='.urlencode($encoded));
                }

                // Existing user — update SIM-ASN token and create Sanctum token
                $sanctumToken = DB::transaction(function () use ($user, $accessToken) {
                    $user->sim_asn_token = [
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token ?? null,
                        'expires_at' => $accessToken->expires_at ?? null,
                    ];
                    $user->save();

                    // Revoke old Sanctum tokens so we only have one active session
                    $user->tokens()->delete();

                    return $user->createToken('sim-asn-token')->plainTextToken;
                });

                Log::info('SIM-ASN login successful', [
                    'user_id' => $user->id,
                    'sim_asn_user_id' => $simAsnUser->id,
                ]);

                return redirect()->away($returnTo.'?access_token='.urlencode($sanctumToken));
            });

            // handleCallback returns a RedirectResponse on success — return it directly
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            // Should not reach here, but handle unexpected return type
            Log::error('SIM-ASN callback: unexpected return type', ['type' => gettype($result)]);

            return redirect()->away($returnTo.'?error=callback_error');

        } catch (\Throwable $e) {
            Log::error('SIM-ASN callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->away($returnTo.'?error='.urlencode($e->getMessage()));
        }
    }
}

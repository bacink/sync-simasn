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
    /**
     * Step 1: Redirect to SIM-ASN authorization page.
     */
    public function initiate(Request $request)
    {
        $request->session()->put('oauth_return_to', $request->get('return_to', '/dashboard'));

        return OauthClient::requestCode('login');
    }

    /**
     * Step 2: Handle callback from SIM-ASN.
     */
    public function callback(Request $request)
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        try {
            $result = OauthClient::handleCallback($request);

            if ($result instanceof RedirectResponse) {
                // OauthClient already built a redirect (typically an error case).
                $targetUrl = $result->getTargetUrl();
                $parsed = parse_url($targetUrl);
                $query = [];
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $query);
                }

                if (isset($query['error'])) {
                    return redirect()->to($returnTo.'?error='.urlencode($query['error']));
                }

                return redirect()->to($returnTo);
            }

            [$simAsnUser, $accessToken] = $result;

            $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

            if (! $user) {
                // Encode SIM-ASN user data + token so frontend can pre-fill registration.
                $payload = json_encode([
                    'user' => $simAsnUser,
                    'token' => $accessToken,
                ]);

                return redirect()->to($returnTo.'?oauth_register='.urlencode(base64_encode($payload)));
            }

            $sanctumToken = DB::transaction(function () use ($user, $accessToken) {
                $user->sim_asn_token = [
                    'access_token' => $accessToken->access_token,
                    'refresh_token' => $accessToken->refresh_token ?? null,
                    'expires_at' => $accessToken->expires_at ?? null,
                ];
                $user->save();

                // Revoke all existing Sanctum tokens so only one is active at a time.
                $user->tokens()->delete();

                return $user->createToken('sim-asn-token')->plainTextToken;
            });

            return redirect()->to($returnTo.'?access_token='.urlencode($sanctumToken));
        } catch (\Throwable $e) {
            Log::error('SIM-ASN OAuth callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to($returnTo.'?error='.urlencode('OAuth callback failed: '.$e->getMessage()));
        }
    }
}

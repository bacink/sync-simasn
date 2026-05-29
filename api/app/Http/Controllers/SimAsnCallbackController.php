<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SIM_ASN\Laravel\Facades\OauthClient;

class SimAsnCallbackController extends Controller
{
    /**
     * Step 1: Redirect to SIM-ASN authorization page.
     */
    public function initiate(Request $request)
    {
        // Store return_to in session for after callback
        $request->session()->put('oauth_return_to', $request->get('return_to', '/dashboard'));

        return OauthClient::requestCode('login');
    }

    /**
     * Step 2: Handle callback from SIM-ASN.
     */
    public function callback(Request $request)
    {
        $returnTo = $request->session()->pull('oauth_return_to', '/dashboard');

        $result = OauthClient::handleCallback($request, function ($simAsnUser, $accessToken) {
            $user = User::where('sim_asn_user_id', $simAsnUser->id)->first();

            if (!$user) {
                return 'user_not_found';
            }

            DB::transaction(function () use ($user, $accessToken) {
                $user->sim_asn_token = [
                    'access_token' => $accessToken->access_token,
                    'refresh_token' => $accessToken->refresh_token ?? null,
                    'expires_at' => $accessToken->expires_at ?? null,
                ];
                $user->save();

                // Revoke existing SIM-ASN tokens so we only have one active token
                $user->tokens()->where('name', 'sim-asn-token')->delete();

                $token = $user->createToken('sim-asn-token')->plainTextToken;
            });

            return $user->id;
        });

        // OauthClient::handleCallback always returns a RedirectResponse.
        // The query string contains either: access_token=<tokenValue> or error=<message>
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            $targetUrl = $result->getTargetUrl();
            $parsed = parse_url($targetUrl);
            $query = [];
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }

            if (isset($query['error'])) {
                return redirect()->to($returnTo.'?error='.urlencode($query['error']));
            }

            // access_token key present — return token + query string for full context
            $accessToken = $query['access_token'] ?? '';
            return redirect()->to($returnTo.'?access_token='.urlencode($accessToken));
        }

        // handleCallback threw an InvalidArgumentException (unknown state)
        return redirect()->to('/login?error='.urlencode($result->getMessage()));
    }
}

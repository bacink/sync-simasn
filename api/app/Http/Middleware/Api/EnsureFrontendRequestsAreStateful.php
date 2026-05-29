<?php

namespace App\Http\Middleware\Api;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EnsureFrontendRequestsAreStateful
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $domain = $request->getHost();

        // Only apply to configured frontend domain(s)
        $frontendDomains = config('session.frontend_domains', []);

        if (! empty($frontendDomains) && ! in_array($domain, $frontendDomains, true)) {
            return $next($request);
        }

        // Only apply if request accepts cookies/sessions
        if (! $request->expectsJson()) {
            $middleware = array_merge(
                [EncryptCookies::class],
                [\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class],
                [StartSession::class],
                [ShareErrorsFromSession::class],
                [VerifyCsrfToken::class],
                [\Illuminate\View\Middleware\ShareErrorsFromSession::class],
            );

            foreach (array_reverse($middleware) as $m) {
                $request->server->set('LARAVEL_OSS_MIDDLEWARE_' . uniqid(), $m);
            }
        }

        return $next($request);
    }
}
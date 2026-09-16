<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response headers that cost nothing functionally: this app has no
 * legitimate reason to be framed by another site, and browsers should never
 * MIME-sniff a response into something other than what it says it is. A full
 * Content-Security-Policy is deliberately NOT added here — the existing
 * Blade views rely on inline onclick="..." handlers throughout, so a
 * script-src policy would break the app unless paired with a larger refactor
 * to nonces/external handlers.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}

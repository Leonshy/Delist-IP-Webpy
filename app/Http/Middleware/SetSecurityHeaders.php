<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent browsers from MIME-sniffing a response away from the declared content-type
        $response->header('X-Content-Type-Options', 'nosniff');

        // Clickjacking protection: prevent the page from being framed
        $response->header('X-Frame-Options', 'DENY');

        // XSS protection: enable XSS filter in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // Referrer policy: control how much referrer information is shared
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Strict Transport Security (HSTS): enforce HTTPS
        if (config('app.env') === 'production') {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP: Content Security Policy for XSS prevention
        $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' challenges.cloudflare.com; frame-src challenges.cloudflare.com; connect-src 'self' challenges.cloudflare.com;");

        return $response;
    }
}
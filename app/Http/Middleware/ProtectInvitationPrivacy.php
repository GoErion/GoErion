<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Company\InvitationToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect application handling after routing, before cookies and sessions.
 * Production proxy/CDN/WAF access logs must separately redact entry-token query
 * data: logging before PHP receives the request is outside this middleware.
 */
final class ProtectInvitationPrivacy
{
    public function handle(Request $request, Closure $next): Response
    {
        $sensitive = $request->routeIs('invitations.*', 'company.invitations.*');
        if ($sensitive) {
            config(['debugbar.enabled' => false]);
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }
        }

        if ($request->routeIs('invitations.entry')) {
            $token = $request->query('token');
            $digest = is_string($token) && preg_match('/\A[A-Za-z0-9_-]{43}\z/', $token)
                ? app(InvitationToken::class)->digest($token) : null;
            unset($token);
            $server = $request->server->all();
            $server['REQUEST_URI'] = $request->getBaseUrl().'/invitations/entry';
            $server['QUERY_STRING'] = '';
            unset($server['HTTP_REFERER']);
            $request->initialize([], [], $request->attributes->all(), $request->cookies->all(), [], $server);
            $request->attributes->set('invitation_digest', $digest);
        }

        $response = $next($request);

        return $sensitive ? self::headers($response) : $response;
    }

    public static function headers(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}

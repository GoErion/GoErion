<?php

use App\Actions\Auth\ResolveAuthenticatedDestination;
use App\Http\Middleware\ProtectInvitationPrivacy;
use App\Models\Auth\User;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [ProtectInvitationPrivacy::class]);
        $middleware->prependToPriorityList(EncryptCookies::class, ProtectInvitationPrivacy::class);
        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(function (Request $request): string {
            /** @var User $user */
            $user = $request->user();

            return app(ResolveAuthenticatedDestination::class)->handle($user);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response): Response {
            if (request()->routeIs('invitations.*') && $response->getStatusCode() >= 500) {
                $response = new Response('This invitation is not available. Please try again later.', $response->getStatusCode());
            }

            return (request()->routeIs('invitations.*', 'company.invitations.*') || request()->is('invitations/*', 'company/*/invitations*'))
                ? ProtectInvitationPrivacy::headers($response) : $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

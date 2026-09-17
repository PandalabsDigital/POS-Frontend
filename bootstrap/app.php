<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\UseRequestRootUrl;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->trustProxies(at: '*');
        $middleware->trustHosts(at: function () {
            return array_values(array_unique(array_filter([
                '127.0.0.1',
                'localhost',
                request()->getHost(),
            ])));
        });
        $middleware->web(prepend: [
            UseRequestRootUrl::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function (Request $request): string {
            return $request->user()?->isAdmin()
                ? route('dashboard')
                : route('pos.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        URL::forceRootUrl($request->root());

        $forwarded = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        if ($request->secure() || $forwarded === 'https') {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}

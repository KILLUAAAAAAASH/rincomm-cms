<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveAccountMiddleware
{
    // Handle an incoming request.
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isActive()) {
            abort(403, 'Your account is inactive.');
        }

        return $next($request);
    }
}
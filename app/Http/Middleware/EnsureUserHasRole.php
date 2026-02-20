<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            abort(403, 'Sizda bu sahifaga kirish huquqi yo\'q.');
        }

        return $next($request);
    }
}

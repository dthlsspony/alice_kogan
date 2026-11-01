<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Админ-раздел закрыт: для гостей и обычных пользователей — 404
     * (раздел не существует), а не 403 (существует, но запрещён).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            abort(404);
        }

        return $next($request);
    }
}

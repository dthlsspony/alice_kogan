<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Лёгкий счётчик посещений: один запрос — одна запись visits.
 * Админку, API и статику не считаем; ботов и повторные просмотры той же
 * страницы в рамках сессии за 30 минут отбрасываем.
 */
class TrackVisit
{
    private const SKIP_PREFIXES = ['admin', 'api', 'storage', 'build', 'images', 'fonts', '_'];

    private const DEDUPE_MINUTES = 30;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if ($this->shouldTrack($request)) {
                $this->track($request);
            }
        } catch (\Throwable) {
            // Учёт посещений не должен ломать страницу.
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax()) {
            return false;
        }

        $first = strtolower(explode('/', ltrim($request->path(), '/'))[0]);

        if (in_array($first, self::SKIP_PREFIXES, true)) {
            return false;
        }

        return ! preg_match('/bot|crawl|spider|slurp|curl|wget|headless/i', (string) $request->userAgent());
    }

    private function track(Request $request): void
    {
        $path = mb_substr($request->path() === '/' ? '/' : '/'.$request->path(), 0, 500);
        $sessionId = (string) $request->session()->getId();
        $ipHash = md5((string) $request->ip().config('app.key'));

        $seen = Visit::query()
            ->where('path', $path)
            ->where(fn ($q) => $q->where('session_id', $sessionId)->orWhere('ip_hash', $ipHash))
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->exists();

        if ($seen) {
            return;
        }

        Visit::query()->create([
            'path' => $path,
            'user_id' => Auth::id(),
            'session_id' => $sessionId,
            'ip_hash' => $ipHash,
            'created_at' => now(),
        ]);
    }
}

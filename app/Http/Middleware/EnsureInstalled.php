<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // Allow testing environment to bypass unless specifically testing installer flow
        if (app()->environment('testing') && !config('app.test_ensure_installed', false)) {
            return $next($request);
        }

        $lockFile = storage_path('installed.lock');

        if (!file_exists($lockFile)) {
            if (!$request->is('install*') && !$request->is('up') && !$request->is('build/*') && !$request->is('assets/*')) {
                return redirect('/install');
            }
        }

        return $next($request);
    }
}

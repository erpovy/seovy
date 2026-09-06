<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $lockFile = storage_path('installed.lock');

        if (!file_exists($lockFile)) {
            return redirect('/install');
        }

        return $next($request);
    }
}

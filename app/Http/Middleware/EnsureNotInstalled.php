<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $lockFile = storage_path('installed.lock');

        if (file_exists($lockFile)) {
            abort(403, 'Uygulama kurulumu daha önce başarıyla tamamlanmış ve kalıcı olarak kilitlenmiştir.');
        }

        return $next($request);
    }
}

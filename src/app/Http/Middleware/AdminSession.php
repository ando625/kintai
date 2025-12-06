<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class AdminSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if ($request->is('admin/*')) {
            Config::set('session.cookie', 'admin_session');
        } else {
            Config::set('session.cookie', env('SESSION_COOKIE', 'laravel_session'));
        }

        return $next($request);
    }
}

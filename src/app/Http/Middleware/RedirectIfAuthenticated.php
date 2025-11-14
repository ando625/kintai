<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\RegisterResponse;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // adminならadmin home, webならuser home
                if ($guard === 'admin') {
                    return redirect()->route('admin.index');
                }
                if ($guard === 'web') {
                    if ($request->routeIs('register') || $request->routeIs('login')) {
                        continue; // 登録・ログインはリダイレクトしない
                    }
                    if (! $request->user()->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice');
                    }
                    return redirect()->route('user.check-in');
                }
            }
        }

        return $next($request);
    }
}

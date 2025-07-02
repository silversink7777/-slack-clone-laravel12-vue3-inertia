<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // adminルートの場合はadminダッシュボードへ
                if ($request->is('admin') || $request->is('admin/*')) {
                    return redirect()->route('admin.dashboard');
                }
                // 通常はユーザーダッシュボードへ
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // adminルートの場合はadminダッシュボードへ
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.dashboard');
        }
        
        // 通常はユーザーダッシュボードへ
        return '/dashboard';
    }
} 
<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // adminガードで未認証の場合はadminログイン画面へ
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }
        // それ以外は通常のログイン画面へ
        return route('login');
    }
} 
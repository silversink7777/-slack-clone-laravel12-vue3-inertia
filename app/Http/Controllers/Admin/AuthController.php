<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Adminログインページを表示
     */
    public function showLoginForm()
    {
        return Inertia::render('Admin/Auth/Login');
    }

    /**
     * Adminログイン処理
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $credentials['is_active'] = true;

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $admin = Auth::guard('admin')->user();
            $admin->update(['last_login_at' => now()]);

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['提供された認証情報が正しくありません。'],
        ]);
    }

    /**
     * Adminログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Adminダッシュボードを表示
     */
    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();
        
        // 統計データを取得
        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_channels' => \App\Models\Channel::count(),
            'total_messages' => \App\Models\Message::count(),
            'online_users' => \App\Models\UserOnlineStatus::where('online', true)->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'admin' => $admin,
            'stats' => $stats,
        ]);
    }
}

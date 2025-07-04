<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * 現在のユーザーのテーマ設定を取得
     */
    public function getTheme(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'theme' => $user->theme_preference ?? 'system'
        ]);
    }

    /**
     * ユーザーのテーマ設定を更新
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'theme' => 'required|in:light,dark,system'
        ]);

        $user->update([
            'theme_preference' => $request->theme
        ]);

        return response()->json([
            'message' => 'Theme updated successfully',
            'theme' => $user->theme_preference
        ]);
    }
}

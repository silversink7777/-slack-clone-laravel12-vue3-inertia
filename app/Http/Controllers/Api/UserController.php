<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * ユーザーを検索
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $currentUserId = auth()->id();

        if (empty($query)) {
            return response()->json(['users' => []]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', $currentUserId) // 自分を除外
            ->with(['onlineStatus'])
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'online' => $user->onlineStatus?->online ?? false,
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * 全ユーザー一覧を取得（DM開始用）
     */
    public function index(Request $request): JsonResponse
    {
        $currentUserId = auth()->id();
        $search = $request->get('search', '');

        $query = User::where('id', '!=', $currentUserId); // 自分を除外

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $users = $query->with(['onlineStatus'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'online' => $user->onlineStatus?->online ?? false,
                ];
            });

        return response()->json(['users' => $users]);
    }
} 
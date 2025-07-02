<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\UserOnlineStatusRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UserOnlineStatusController extends Controller
{
    protected $statusRepository;

    public function __construct(UserOnlineStatusRepositoryInterface $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    // 全ユーザーのオンライン状態を取得
    public function index(): JsonResponse
    {
        $statuses = $this->statusRepository->getAllWithUser();
        Log::info('UserOnlineStatus index called', ['count' => $statuses->count(), 'data' => $statuses->toArray()]);
        return response()->json($statuses);
    }

    // GETリクエストでオンライン状態を更新（CSRFトークン問題回避）
    public function updateOnline(): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            Log::warning('UserOnlineStatus updateOnline called without user');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Log::info('UserOnlineStatus updateOnline called', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
        
        $status = $this->statusRepository->updateOrCreate($user->id, [
            'online' => true,
        ]);
        
        Log::info('UserOnlineStatus updated/created via GET', ['status' => $status->toArray()]);
        return response()->json($status);
    }

    // ログインユーザーのオンライン状態を更新
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            Log::warning('UserOnlineStatus update called without user');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // バリデーション
        $validated = $request->validate([
            'online' => 'required|boolean',
        ]);
        
        Log::info('UserOnlineStatus update called', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'request_data' => $validated
        ]);
        
        $status = $this->statusRepository->updateOrCreate($user->id, [
            'online' => $validated['online'],
        ]);
        
        Log::info('UserOnlineStatus updated/created', ['status' => $status->toArray()]);
        return response()->json($status);
    }
}

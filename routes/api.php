<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserOnlineStatusController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\Api\UserController;

Route::middleware(['auth:sanctum', 'web'])->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user-online-status', [UserOnlineStatusController::class, 'index']);
    Route::post('/user-online-status', [UserOnlineStatusController::class, 'update']);
    
    // チャンネル関連のルート
    Route::get('/channels', [ChannelController::class, 'index']);
    Route::post('/channels', [ChannelController::class, 'store']);
    // チャンネルメンバー取得はWebルートで処理するため削除

    // ユーザー関連のルート
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/search', [UserController::class, 'search']);

    // ダイレクトメッセージ関連のルート
    Route::get('/direct-messages/partners', [DirectMessageController::class, 'getPartners']);
    Route::get('/direct-messages/conversation', [DirectMessageController::class, 'getConversation']);
    Route::post('/direct-messages', [DirectMessageController::class, 'store']);
    Route::post('/direct-messages/start', [DirectMessageController::class, 'startConversation']);
    Route::put('/direct-messages/{id}', [DirectMessageController::class, 'update']);
    Route::delete('/direct-messages/{id}', [DirectMessageController::class, 'destroy']);
    Route::get('/direct-messages/unread-count', [DirectMessageController::class, 'getUnreadCount']);
    Route::post('/direct-messages/{id}/mark-read', [DirectMessageController::class, 'markAsRead']);
});

// 認証状態確認用のルート（デバッグ用）
Route::get('/auth/check', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver()
    ]);
});

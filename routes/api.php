<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserOnlineStatusController;
use App\Http\Controllers\Api\ChannelController;

Route::middleware('auth:web')->group(function() {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user-online-status', [UserOnlineStatusController::class, 'index']);
    Route::post('/user-online-status', [UserOnlineStatusController::class, 'update']);
    
    // チャンネル関連のルート
    Route::get('/channels', [ChannelController::class, 'index']);
    Route::post('/channels', [ChannelController::class, 'store']);
    // チャンネルメンバー取得はWebルートで処理するため削除
});

// 認証状態確認用のルート（デバッグ用）
Route::get('/auth/check', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver()
    ]);
});

<?php

use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\UserOnlineStatusController;
use App\Http\Controllers\Api\ChannelInvitationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserManagementController as AdminUserManagementController;
use App\Http\Controllers\Admin\ChannelManagementController;
use App\Http\Controllers\Admin\MessageManagementController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::delete('/channels/{id}', [ChannelController::class, 'destroy'])->name('channels.destroy');
    Route::post('/channels/{id}/leave', [ChannelController::class, 'leave'])->name('channels.leave');
    Route::get('/channels/{id}/members', [ChannelController::class, 'members'])->name('channels.members');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::put('/messages/{id}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::patch('/messages/{id}/restore', [MessageController::class, 'restore'])->name('messages.restore');
    
    // オンライン状態API
    Route::get('/user-online-status', [UserOnlineStatusController::class, 'index'])->name('user-online-status.index');
    Route::post('/user-online-status', [UserOnlineStatusController::class, 'update'])->name('user-online-status.update');
    Route::get('/user-online-status-update', [UserOnlineStatusController::class, 'updateOnline'])->name('user-online-status.updateOnline');
    
    // チャンネル招待API
    Route::post('/channels/{id}/invite', [ChannelInvitationController::class, 'invite'])->name('channel-invitations.invite');
    Route::get('/channels/{id}/invitations', [ChannelInvitationController::class, 'index'])->name('channel-invitations.index');
    Route::post('/invitations/{id}/respond', [ChannelInvitationController::class, 'respond'])->name('channel-invitations.respond');
    Route::delete('/invitations/{id}', [ChannelInvitationController::class, 'cancel'])->name('channel-invitations.cancel');
    Route::get('/my-invitations', [ChannelInvitationController::class, 'myInvitations'])->name('channel-invitations.my');
    
    // ユーザーAPI
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

    Route::get('/channels/search', [ChannelController::class, 'search'])->name('channels.search');
    Route::get('/messages/search', [MessageController::class, 'search'])->name('messages.search');
});

// Admin認証ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // 未認証ユーザー向け
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // 認証済みAdmin向け
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // ユーザー管理
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserManagementController::class, 'index'])->name('index');
            Route::get('/{user}', [AdminUserManagementController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [AdminUserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [AdminUserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [AdminUserManagementController::class, 'destroy'])->name('destroy');
        });

        // チャンネル管理
        Route::prefix('channels')->name('channels.')->group(function () {
            Route::get('/', [AdminUserManagementController::class, 'channels'])->name('index');
        });

        // メッセージ管理
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [AdminUserManagementController::class, 'messages'])->name('index');
        });
    });
});

// 招待通知ページ（未認証ユーザーもアクセス可能）
Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations');

// 未認証ユーザー向けの招待情報取得API
Route::get('/api/invitations/{id}/info', [ChannelInvitationController::class, 'getInvitationInfo'])->name('api.invitations.info');

Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {
    Route::get('channels', [ChannelManagementController::class, 'index'])->name('channels.index');
    Route::get('channels/create', [ChannelManagementController::class, 'create'])->name('channels.create');
    Route::post('channels', [ChannelManagementController::class, 'store'])->name('channels.store');
    Route::get('channels/{id}', [ChannelManagementController::class, 'show'])->name('channels.show');
    Route::get('channels/{id}/edit', [ChannelManagementController::class, 'edit'])->name('channels.edit');
    Route::put('channels/{id}', [ChannelManagementController::class, 'update'])->name('channels.update');
    Route::delete('channels/{id}', [ChannelManagementController::class, 'destroy'])->name('channels.destroy');
    Route::get('messages', [MessageManagementController::class, 'index'])->name('messages.index');
    Route::get('messages/{id}', [MessageManagementController::class, 'show'])->name('messages.show');
    Route::delete('messages/{id}', [MessageManagementController::class, 'destroy'])->name('messages.destroy');
});

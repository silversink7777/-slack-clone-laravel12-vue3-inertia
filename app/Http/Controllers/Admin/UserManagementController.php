<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Channel;
use App\Models\Message;
use App\Models\UserOnlineStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    /**
     * ユーザー一覧を表示
     */
    public function index(Request $request)
    {
        $query = User::with(['onlineStatus', 'channels']);

        // 検索機能
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // オンライン状態でフィルタ
        if ($request->filled('online_status')) {
            $onlineStatus = $request->get('online_status');
            if ($onlineStatus === 'online') {
                $query->whereHas('onlineStatus', function ($q) {
                    $q->where('online', true);
                });
            } elseif ($onlineStatus === 'offline') {
                $query->whereHas('onlineStatus', function ($q) {
                    $q->where('online', false);
                })->orWhereDoesntHave('onlineStatus');
            }
        }

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'admin' => auth('admin')->user(),
            'users' => $users,
            'filters' => $request->only(['search', 'online_status']),
        ]);
    }

    /**
     * ユーザー詳細を表示
     */
    public function show(User $user)
    {
        $user->load([
            'onlineStatus',
            'channels',
            'messages' => function ($query) {
                $query->latest()->limit(10);
            },
            'sentInvitations',
            'receivedInvitations',
        ]);

        $stats = [
            'total_messages' => $user->messages()->count(),
            'total_channels' => $user->channels()->count(),
            'sent_invitations' => $user->sentInvitations()->count(),
            'received_invitations' => $user->receivedInvitations()->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'admin' => auth('admin')->user(),
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    /**
     * ユーザー編集フォームを表示
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'admin' => auth('admin')->user(),
            'user' => $user,
        ]);
    }

    /**
     * ユーザー情報を更新
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only(['name', 'email']));

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * ユーザーを削除
     */
    public function destroy(User $user)
    {
        // 関連データも削除
        $user->messages()->delete();
        $user->onlineStatus()->delete();
        $user->sentInvitations()->delete();
        $user->receivedInvitations()->delete();
        $user->channelMemberships()->delete();
        
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを削除しました。');
    }

    /**
     * チャンネル管理
     */
    public function channels(Request $request)
    {
        $query = Channel::with(['members.user', 'messages']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $channels = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Channels/Index', [
            'admin' => auth('admin')->user(),
            'channels' => $channels,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * メッセージ管理
     */
    public function messages(Request $request)
    {
        $query = Message::with(['user', 'channel']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('content', 'like', "%{$search}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->get('channel_id'));
        }

        $messages = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Messages/Index', [
            'admin' => auth('admin')->user(),
            'messages' => $messages,
            'filters' => $request->only(['search', 'user_id', 'channel_id']),
        ]);
    }
}

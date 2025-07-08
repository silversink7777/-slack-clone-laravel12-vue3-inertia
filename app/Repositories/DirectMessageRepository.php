<?php

namespace App\Repositories;

use App\Models\DirectMessage;
use App\Models\User;
use App\Repositories\Interfaces\DirectMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DirectMessageRepository implements DirectMessageRepositoryInterface
{
    /**
     * ダイレクトメッセージを作成
     */
    public function create(array $data): DirectMessage
    {
        return DirectMessage::create($data);
    }

    /**
     * ダイレクトメッセージをIDで取得
     */
    public function findById(string $id): ?DirectMessage
    {
        return DirectMessage::find($id);
    }

    /**
     * 2つのユーザー間のダイレクトメッセージ履歴を取得
     */
    public function getConversation(int $userId1, int $userId2, int $limit = 50): Collection
    {
        return DirectMessage::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)
                  ->where('receiver_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)
                  ->where('receiver_id', $userId1);
        })->with(['sender', 'receiver'])
          ->orderBy('created_at', 'desc')
          ->limit($limit)
          ->get()
          ->reverse();
    }

    /**
     * ユーザーのダイレクトメッセージパートナー一覧を取得
     */
    public function getPartners(int $userId): Collection
    {
        $sentPartners = DirectMessage::where('sender_id', $userId)
            ->select('receiver_id')
            ->distinct()
            ->pluck('receiver_id');

        $receivedPartners = DirectMessage::where('receiver_id', $userId)
            ->select('sender_id')
            ->distinct()
            ->pluck('sender_id');

        $partnerIds = $sentPartners->merge($receivedPartners)->unique();

        $users = User::whereIn('id', $partnerIds)
            ->with(['onlineStatus'])
            ->get();

        $result = $users->map(function ($user) use ($userId) {
            $unreadCount = DirectMessage::where('sender_id', $user->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $lastMessage = DirectMessage::where(function ($query) use ($userId, $user) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $userId);
            })->orWhere(function ($query) use ($userId, $user) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $user->id);
            })->latest()
              ->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'online' => $user->onlineStatus?->online ?? false,
                'unread_count' => $unreadCount,
                'last_message' => $lastMessage ? [
                    'content' => $lastMessage->content,
                    'created_at' => $lastMessage->created_at,
                    'is_sender' => $lastMessage->sender_id === $userId,
                ] : null,
            ];
        });

        // Eloquent\Collection型で返す
        return new \Illuminate\Database\Eloquent\Collection($result->all());
    }

    /**
     * 未読メッセージ数を取得
     */
    public function getUnreadCount(int $userId): int
    {
        return DirectMessage::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * メッセージを既読にする
     */
    public function markAsRead(string $messageId, int $userId): bool
    {
        $message = DirectMessage::find($messageId);
        
        if (!$message || $message->receiver_id !== $userId) {
            return false;
        }

        return $message->markAsRead();
    }

    /**
     * 特定のユーザーとのメッセージを全て既読にする
     */
    public function markConversationAsRead(int $userId, int $partnerId): bool
    {
        return DirectMessage::where('sender_id', $partnerId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * ダイレクトメッセージを更新
     */
    public function update(DirectMessage $message, array $data): bool
    {
        return $message->update($data);
    }

    /**
     * ダイレクトメッセージを削除（ソフトデリート）
     */
    public function delete(DirectMessage $message): bool
    {
        return $message->delete();
    }

    /**
     * ダイレクトメッセージを復元
     */
    public function restore(DirectMessage $message): bool
    {
        return $message->restore();
    }

    /**
     * メッセージがユーザーのものかチェック
     */
    public function isUserMessage(string $messageId, int $userId): bool
    {
        return DirectMessage::where('id', $messageId)
            ->where('sender_id', $userId)
            ->exists();
    }
} 
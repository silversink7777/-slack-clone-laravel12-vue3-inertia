<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * メッセージを作成
     */
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    /**
     * メッセージをIDで取得
     */
    public function findById(string $id): ?Message
    {
        return Message::find($id);
    }

    /**
     * メッセージをIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?Message
    {
        return Message::with($relations)->find($id);
    }

    /**
     * チャンネルのメッセージ一覧を取得
     */
    public function getByChannelId(string $channelId): Collection
    {
        return Message::with('user')
            ->where('channel_id', $channelId)
            ->whereNull('deleted_at')
            ->latest()
            ->get();
    }

    /**
     * ユーザーがメンバーのチャンネルのメッセージ一覧を取得
     */
    public function getByUserChannels(array $channelIds): Collection
    {
        return Message::with('user')
            ->whereIn('channel_id', $channelIds)
            ->whereNull('deleted_at')
            ->latest()
            ->get();
    }

    /**
     * メッセージを更新
     */
    public function update(Message $message, array $data): bool
    {
        return $message->update($data);
    }

    /**
     * メッセージを削除（ソフトデリート）
     */
    public function delete(Message $message): bool
    {
        return $message->delete();
    }

    /**
     * メッセージを復元
     */
    public function restore(Message $message): bool
    {
        return $message->restore();
    }

    /**
     * メッセージがユーザーのものかチェック
     */
    public function isUserMessage(string $messageId, int $userId): bool
    {
        return Message::where('id', $messageId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * 管理画面用: 検索・ページネーション・ユーザー/チャンネル絞り込み
     */
    public function searchWithFilters(array $filters, int $perPage = 20)
    {
        $query = Message::with(['user', 'channel']);
        if (!empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }
        return $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
    }
} 
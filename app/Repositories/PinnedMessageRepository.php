<?php

namespace App\Repositories;

use App\Models\PinnedMessage;
use App\Repositories\Interfaces\PinnedMessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PinnedMessageRepository implements PinnedMessageRepositoryInterface
{
    /**
     * ピン留めを作成
     */
    public function create(array $data): PinnedMessage
    {
        return PinnedMessage::create($data);
    }

    /**
     * ピン留めを削除
     */
    public function delete(int $id): bool
    {
        $pinnedMessage = PinnedMessage::find($id);
        return $pinnedMessage ? $pinnedMessage->delete() : false;
    }

    /**
     * チャンネルのピン留めメッセージ一覧を取得
     */
    public function getByChannelId(int $channelId): Collection
    {
        return PinnedMessage::with(['message.user', 'pinnedByUser'])
            ->where('channel_id', $channelId)
            ->latest()
            ->get();
    }

    /**
     * メッセージが特定のチャンネルでピン留めされているかチェック
     */
    public function isPinned(int $messageId, int $channelId): bool
    {
        return PinnedMessage::where('message_id', $messageId)
            ->where('channel_id', $channelId)
            ->exists();
    }

    /**
     * ピン留めをIDで取得
     */
    public function findById(int $id): ?PinnedMessage
    {
        return PinnedMessage::find($id);
    }

    /**
     * メッセージIDとチャンネルIDでピン留めを取得
     */
    public function findByMessageAndChannel(int $messageId, int $channelId): ?PinnedMessage
    {
        return PinnedMessage::where('message_id', $messageId)
            ->where('channel_id', $channelId)
            ->first();
    }
} 
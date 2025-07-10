<?php

namespace App\Repositories\Interfaces;

use App\Models\PinnedMessage;
use Illuminate\Database\Eloquent\Collection;

interface PinnedMessageRepositoryInterface
{
    /**
     * ピン留めを作成
     */
    public function create(array $data): PinnedMessage;

    /**
     * ピン留めを削除
     */
    public function delete(int $id): bool;

    /**
     * チャンネルのピン留めメッセージ一覧を取得
     */
    public function getByChannelId(int $channelId): Collection;

    /**
     * メッセージが特定のチャンネルでピン留めされているかチェック
     */
    public function isPinned(int $messageId, int $channelId): bool;

    /**
     * ピン留めをIDで取得
     */
    public function findById(int $id): ?PinnedMessage;

    /**
     * メッセージIDとチャンネルIDでピン留めを取得
     */
    public function findByMessageAndChannel(int $messageId, int $channelId): ?PinnedMessage;
} 
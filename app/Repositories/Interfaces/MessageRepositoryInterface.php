<?php

namespace App\Repositories\Interfaces;

use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    /**
     * メッセージを作成
     */
    public function create(array $data): Message;

    /**
     * メッセージをIDで取得
     */
    public function findById(string $id): ?Message;

    /**
     * メッセージをIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?Message;

    /**
     * チャンネルのメッセージ一覧を取得
     */
    public function getByChannelId(string $channelId): Collection;

    /**
     * ユーザーがメンバーのチャンネルのメッセージ一覧を取得
     */
    public function getByUserChannels(array $channelIds): Collection;

    /**
     * メッセージを更新
     */
    public function update(Message $message, array $data): bool;

    /**
     * メッセージを削除（ソフトデリート）
     */
    public function delete(Message $message): bool;

    /**
     * メッセージを復元
     */
    public function restore(Message $message): bool;

    /**
     * メッセージがユーザーのものかチェック
     */
    public function isUserMessage(string $messageId, int $userId): bool;

    /**
     * 管理画面用: 検索・ページネーション・ユーザー/チャンネル絞り込み
     */
    public function searchWithFilters(array $filters, int $perPage = 20);
} 
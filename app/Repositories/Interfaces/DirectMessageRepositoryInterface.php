<?php

namespace App\Repositories\Interfaces;

use App\Models\DirectMessage;
use Illuminate\Database\Eloquent\Collection;

interface DirectMessageRepositoryInterface
{
    /**
     * ダイレクトメッセージを作成
     */
    public function create(array $data): DirectMessage;

    /**
     * ダイレクトメッセージをIDで取得
     */
    public function findById(string $id): ?DirectMessage;

    /**
     * 2つのユーザー間のダイレクトメッセージ履歴を取得
     */
    public function getConversation(int $userId1, int $userId2, int $limit = 50): Collection;

    /**
     * ユーザーのダイレクトメッセージパートナー一覧を取得
     */
    public function getPartners(int $userId): Collection;

    /**
     * 未読メッセージ数を取得
     */
    public function getUnreadCount(int $userId): int;

    /**
     * メッセージを既読にする
     */
    public function markAsRead(string $messageId, int $userId): bool;

    /**
     * 特定のユーザーとのメッセージを全て既読にする
     */
    public function markConversationAsRead(int $userId, int $partnerId): bool;

    /**
     * ダイレクトメッセージを更新
     */
    public function update(DirectMessage $message, array $data): bool;

    /**
     * ダイレクトメッセージを削除（ソフトデリート）
     */
    public function delete(DirectMessage $message): bool;

    /**
     * ダイレクトメッセージを復元
     */
    public function restore(DirectMessage $message): bool;

    /**
     * メッセージがユーザーのものかチェック
     */
    public function isUserMessage(string $messageId, int $userId): bool;
} 
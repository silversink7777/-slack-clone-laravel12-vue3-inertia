<?php

namespace App\Repositories\Interfaces;

use App\Models\ChannelInvitation;
use Illuminate\Database\Eloquent\Collection;

interface ChannelInvitationRepositoryInterface
{
    /**
     * 招待を作成
     */
    public function create(array $data): ChannelInvitation;

    /**
     * 招待をIDで取得
     */
    public function findById(string $id): ?ChannelInvitation;

    /**
     * 招待をIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?ChannelInvitation;

    /**
     * チャンネルの招待一覧を取得
     */
    public function getByChannelId(string $channelId): Collection;

    /**
     * ユーザーが受け取った招待一覧を取得
     */
    public function getByUserId(int $userId): Collection;

    /**
     * メールアドレスによる招待一覧を取得
     */
    public function getByEmail(string $email): Collection;

    /**
     * 有効な招待をIDで取得
     */
    public function findValidById(string $id): ?ChannelInvitation;

    /**
     * 既存の招待をチェック
     */
    public function checkExistingInvitation(string $channelId, ?int $inviteeId = null, ?string $inviteeEmail = null): ?ChannelInvitation;

    /**
     * 招待を更新
     */
    public function update(ChannelInvitation $invitation, array $data): bool;

    /**
     * 招待を削除
     */
    public function delete(ChannelInvitation $invitation): bool;

    /**
     * 招待を承認
     */
    public function accept(ChannelInvitation $invitation): bool;

    /**
     * 招待を拒否
     */
    public function decline(ChannelInvitation $invitation): bool;

    /**
     * 期限切れの招待を期限切れにマーク
     */
    public function markAsExpired(ChannelInvitation $invitation): bool;
} 
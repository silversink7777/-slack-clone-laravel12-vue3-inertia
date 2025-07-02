<?php

namespace App\Repositories;

use App\Models\ChannelInvitation;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class ChannelInvitationRepository implements ChannelInvitationRepositoryInterface
{
    /**
     * 招待を作成
     */
    public function create(array $data): ChannelInvitation
    {
        return ChannelInvitation::create($data);
    }

    /**
     * 招待をIDで取得
     */
    public function findById(string $id): ?ChannelInvitation
    {
        return ChannelInvitation::find($id);
    }

    /**
     * 招待をIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?ChannelInvitation
    {
        return ChannelInvitation::with($relations)->find($id);
    }

    /**
     * チャンネルの招待一覧を取得
     */
    public function getByChannelId(string $channelId): Collection
    {
        return ChannelInvitation::with(['invitee', 'inviter'])
            ->where('channel_id', $channelId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * ユーザーが受け取った招待一覧を取得
     */
    public function getByUserId(int $userId): Collection
    {
        return ChannelInvitation::with(['channel', 'inviter'])
            ->where('invitee_id', $userId)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * メールアドレスによる招待一覧を取得
     */
    public function getByEmail(string $email): Collection
    {
        return ChannelInvitation::with(['channel', 'inviter'])
            ->where('invitee_email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 有効な招待をIDで取得
     */
    public function findValidById(string $id): ?ChannelInvitation
    {
        return ChannelInvitation::with(['channel', 'inviter'])
            ->where('id', $id)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * 既存の招待をチェック
     */
    public function checkExistingInvitation(string $channelId, ?int $inviteeId = null, ?string $inviteeEmail = null): ?ChannelInvitation
    {
        $query = ChannelInvitation::where('channel_id', $channelId)
            ->where('status', 'pending');

        if ($inviteeId) {
            $query->where('invitee_id', $inviteeId);
        }

        if ($inviteeEmail) {
            $query->where('invitee_email', $inviteeEmail);
        }

        return $query->first();
    }

    /**
     * 招待を更新
     */
    public function update(ChannelInvitation $invitation, array $data): bool
    {
        return $invitation->update($data);
    }

    /**
     * 招待を削除
     */
    public function delete(ChannelInvitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * 招待を承認
     */
    public function accept(ChannelInvitation $invitation): bool
    {
        $invitation->accept();
        return true;
    }

    /**
     * 招待を拒否
     */
    public function decline(ChannelInvitation $invitation): bool
    {
        $invitation->decline();
        return true;
    }

    /**
     * 期限切れの招待を期限切れにマーク
     */
    public function markAsExpired(ChannelInvitation $invitation): bool
    {
        $invitation->markAsExpired();
        return true;
    }
} 
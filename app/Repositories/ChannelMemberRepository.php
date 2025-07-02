<?php

namespace App\Repositories;

use App\Models\ChannelMember;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;

class ChannelMemberRepository implements ChannelMemberRepositoryInterface
{
    /**
     * チャンネルメンバーを作成
     */
    public function create(array $data): ChannelMember
    {
        // joined_atが設定されていない場合は現在時刻を設定
        if (!isset($data['joined_at'])) {
            $data['joined_at'] = now();
        }
        
        return ChannelMember::create($data);
    }

    /**
     * チャンネルメンバーを取得
     */
    public function findByChannelAndUser(int $channelId, int $userId): ?ChannelMember
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * ユーザーがチャンネルのメンバーかチェック
     */
    public function isMember(int $channelId, int $userId): bool
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * ユーザーがチャンネルの管理者かチェック
     */
    public function isAdmin(int $channelId, int $userId): bool
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->exists();
    }

    /**
     * チャンネルの管理者数を取得
     */
    public function getAdminCount(int $channelId): int
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('role', 'admin')
            ->count();
    }

    /**
     * チャンネルメンバーを削除
     */
    public function removeMember(int $channelId, int $userId): bool
    {
        return ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->delete();
    }
} 
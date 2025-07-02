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
} 
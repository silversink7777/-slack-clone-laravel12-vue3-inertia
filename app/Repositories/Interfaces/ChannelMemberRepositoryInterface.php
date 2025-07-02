<?php

namespace App\Repositories\Interfaces;

use App\Models\ChannelMember;

interface ChannelMemberRepositoryInterface
{
    /**
     * チャンネルメンバーを作成
     */
    public function create(array $data): ChannelMember;

    /**
     * チャンネルメンバーを取得
     */
    public function findByChannelAndUser(int $channelId, int $userId): ?ChannelMember;

    /**
     * ユーザーがチャンネルのメンバーかチェック
     */
    public function isMember(int $channelId, int $userId): bool;

    /**
     * ユーザーがチャンネルの管理者かチェック
     */
    public function isAdmin(int $channelId, int $userId): bool;
} 
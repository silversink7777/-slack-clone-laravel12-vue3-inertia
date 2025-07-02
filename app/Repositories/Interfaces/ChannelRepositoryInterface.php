<?php

namespace App\Repositories\Interfaces;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

interface ChannelRepositoryInterface
{
    /**
     * チャンネルを作成
     */
    public function create(array $data): Channel;

    /**
     * チャンネルをIDで取得
     */
    public function findById(string $id): ?Channel;

    /**
     * チャンネルをIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?Channel;

    /**
     * ユーザーがメンバーのチャンネル一覧を取得
     */
    public function getByUserId(int $userId): Collection;

    /**
     * チャンネル名の重複チェック
     */
    public function isNameExists(string $name): bool;

    /**
     * チャンネルを更新
     */
    public function update(Channel $channel, array $data): bool;

    /**
     * チャンネルを削除
     */
    public function delete(Channel $channel): bool;

    /**
     * ユーザーがチャンネルのメンバーかチェック
     */
    public function isUserMember(string $channelId, int $userId): bool;

    /**
     * ユーザーがチャンネルの管理者かチェック
     */
    public function isUserAdmin(string $channelId, int $userId): bool;
} 
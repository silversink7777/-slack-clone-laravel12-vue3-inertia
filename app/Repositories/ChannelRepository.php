<?php

namespace App\Repositories;

use App\Models\Channel;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ChannelRepository implements ChannelRepositoryInterface
{
    /**
     * チャンネルを作成
     */
    public function create(array $data): Channel
    {
        return Channel::create($data);
    }

    /**
     * チャンネルをIDで取得
     */
    public function findById(string $id): ?Channel
    {
        return Channel::find($id);
    }

    /**
     * チャンネルをIDで取得（リレーション付き）
     */
    public function findByIdWithRelations(string $id, array $relations = []): ?Channel
    {
        return Channel::with($relations)->find($id);
    }

    /**
     * ユーザーがメンバーのチャンネル一覧を取得
     */
    public function getByUserId(int $userId): Collection
    {
        return Channel::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }

    /**
     * チャンネル名の重複チェック
     */
    public function isNameExists(string $name): bool
    {
        return Channel::where('name', $name)->exists();
    }

    /**
     * チャンネルを更新
     */
    public function update(Channel $channel, array $data): bool
    {
        return $channel->update($data);
    }

    /**
     * チャンネルを削除
     */
    public function delete(Channel $channel): bool
    {
        return $channel->delete();
    }

    /**
     * ユーザーがチャンネルのメンバーかチェック
     */
    public function isUserMember(string $channelId, int $userId): bool
    {
        return Channel::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('id', $channelId)->exists();
    }

    /**
     * ユーザーがチャンネルの管理者かチェック
     */
    public function isUserAdmin(string $channelId, int $userId): bool
    {
        return Channel::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('role', 'admin');
        })->where('id', $channelId)->exists();
    }
} 
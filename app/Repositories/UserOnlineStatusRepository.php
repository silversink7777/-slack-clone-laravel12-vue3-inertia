<?php

namespace App\Repositories;

use App\Models\UserOnlineStatus;
use App\Repositories\Interfaces\UserOnlineStatusRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class UserOnlineStatusRepository implements UserOnlineStatusRepositoryInterface
{
    /**
     * オンライン状態を作成
     */
    public function create(array $data): UserOnlineStatus
    {
        return UserOnlineStatus::create($data);
    }

    /**
     * オンライン状態をIDで取得
     */
    public function findById(string $id): ?UserOnlineStatus
    {
        return UserOnlineStatus::find($id);
    }

    /**
     * ユーザーのオンライン状態を取得
     */
    public function findByUserId(int $userId): ?UserOnlineStatus
    {
        return UserOnlineStatus::where('user_id', $userId)->first();
    }

    /**
     * 全ユーザーのオンライン状態を取得
     */
    public function getAllWithUser(): Collection
    {
        return UserOnlineStatus::with('user')->get();
    }

    /**
     * オンライン状態を更新または作成
     */
    public function updateOrCreate(int $userId, array $data): UserOnlineStatus
    {
        return UserOnlineStatus::updateOrCreate(
            ['user_id' => $userId],
            array_merge($data, ['last_active_at' => Carbon::now()])
        );
    }

    /**
     * オンライン状態を更新
     */
    public function update(UserOnlineStatus $status, array $data): bool
    {
        return $status->update($data);
    }

    /**
     * オンライン状態を削除
     */
    public function delete(UserOnlineStatus $status): bool
    {
        return $status->delete();
    }
} 
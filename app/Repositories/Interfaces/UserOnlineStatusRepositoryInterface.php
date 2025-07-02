<?php

namespace App\Repositories\Interfaces;

use App\Models\UserOnlineStatus;
use Illuminate\Database\Eloquent\Collection;

interface UserOnlineStatusRepositoryInterface
{
    /**
     * オンライン状態を作成
     */
    public function create(array $data): UserOnlineStatus;

    /**
     * オンライン状態をIDで取得
     */
    public function findById(string $id): ?UserOnlineStatus;

    /**
     * ユーザーのオンライン状態を取得
     */
    public function findByUserId(int $userId): ?UserOnlineStatus;

    /**
     * 全ユーザーのオンライン状態を取得
     */
    public function getAllWithUser(): Collection;

    /**
     * オンライン状態を更新または作成
     */
    public function updateOrCreate(int $userId, array $data): UserOnlineStatus;

    /**
     * オンライン状態を更新
     */
    public function update(UserOnlineStatus $status, array $data): bool;

    /**
     * オンライン状態を削除
     */
    public function delete(UserOnlineStatus $status): bool;
} 
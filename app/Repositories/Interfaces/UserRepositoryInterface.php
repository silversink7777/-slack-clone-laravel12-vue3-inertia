<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * ユーザーを作成
     */
    public function create(array $data): User;

    /**
     * ユーザーをIDで取得
     */
    public function findById(int $id): ?User;

    /**
     * ユーザーをIDで取得（選択フィールド）
     */
    public function findByIdWithSelect(int $id, array $select = []): ?User;

    /**
     * ユーザーをメールアドレスで取得
     */
    public function findByEmail(string $email): ?User;

    /**
     * ユーザー一覧を取得（ページネーション付き）
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * ユーザーを検索
     */
    public function search(string $search, int $perPage = 20): LengthAwarePaginator;

    /**
     * ユーザーを更新
     */
    public function update(User $user, array $data): bool;

    /**
     * ユーザーを削除
     */
    public function delete(User $user): bool;

    /**
     * メールアドレスの重複チェック
     */
    public function isEmailExists(string $email): bool;
} 
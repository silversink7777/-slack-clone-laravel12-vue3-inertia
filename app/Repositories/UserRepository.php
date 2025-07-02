<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    /**
     * ユーザーを作成
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * ユーザーをIDで取得
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * ユーザーをIDで取得（選択フィールド）
     */
    public function findByIdWithSelect(int $id, array $select = []): ?User
    {
        return User::select($select)->find($id);
    }

    /**
     * ユーザーをメールアドレスで取得
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * ユーザー一覧を取得（ページネーション付き）
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query();

        // フィルターの適用
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->select('id', 'name', 'email')
                    ->orderBy('name')
                    ->paginate($perPage);
    }

    /**
     * ユーザーを検索
     */
    public function search(string $search, int $perPage = 20): LengthAwarePaginator
    {
        return User::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        })
        ->select('id', 'name', 'email')
        ->orderBy('name')
        ->paginate($perPage);
    }

    /**
     * ユーザーを更新
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * ユーザーを削除
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * メールアドレスの重複チェック
     */
    public function isEmailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
} 
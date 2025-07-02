<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $table = 'tbl_channels';
    protected $guarded = [];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * チャンネル招待とのリレーション
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(ChannelInvitation::class);
    }

    /**
     * チャンネルメンバーとのリレーション
     */
    public function members(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    /**
     * チャンネルの管理者を取得
     */
    public function admins()
    {
        return $this->members()->where('role', 'admin');
    }

    /**
     * チャンネルの一般メンバーを取得
     */
    public function regularMembers()
    {
        return $this->members()->where('role', 'member');
    }

    /**
     * ユーザーがチャンネルのメンバーかチェック
     */
    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * ユーザーがチャンネルの管理者かチェック
     */
    public function hasAdmin(int $userId): bool
    {
        return $this->admins()->where('user_id', $userId)->exists();
    }
}

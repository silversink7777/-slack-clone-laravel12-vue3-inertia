<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMember extends Model
{
    use HasFactory;

    protected $table = 'tbl_channel_members';

    protected $fillable = [
        'channel_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * チャンネルとのリレーション
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 管理者かどうかチェック
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * メンバーかどうかチェック
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }
}

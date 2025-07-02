<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelInvitation extends Model
{
    use HasFactory;

    protected $table = 'tbl_channel_invitations';

    protected $fillable = [
        'channel_id',
        'inviter_id',
        'invitee_id',
        'invitee_email',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * チャンネルとのリレーション
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * 招待者とのリレーション
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * 招待されたユーザーとのリレーション
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id')->withDefault([
            'name' => $this->invitee_email ?? 'Unknown User',
            'email' => $this->invitee_email ?? 'unknown@example.com'
        ]);
    }

    /**
     * 有効期限が切れているかチェック
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * 招待が有効かチェック
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * 招待を期限切れにする
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * 招待を承認する
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * 招待を拒否する
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }
}

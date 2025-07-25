<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $table = 'tbl_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'theme_preference',
        'bio',
        'location',
        'website',
        'phone',
        'birth_date',
        'timezone',
        'language',
        'social_links',
        'is_public_profile',
        'last_seen_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'social_links' => 'array',
            'is_public_profile' => 'boolean',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function onlineStatus()
    {
        return $this->hasOne(UserOnlineStatus::class, 'user_id');
    }

    /**
     * 送信した招待とのリレーション
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(ChannelInvitation::class, 'inviter_id');
    }

    /**
     * 受け取った招待とのリレーション
     */
    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(ChannelInvitation::class, 'invitee_id');
    }

    /**
     * チャンネルメンバーシップとのリレーション
     */
    public function channelMemberships(): HasMany
    {
        return $this->hasMany(ChannelMember::class);
    }

    /**
     * 参加しているチャンネルを取得
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'tbl_channel_members', 'user_id', 'channel_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * 管理者として参加しているチャンネルを取得
     */
    public function adminChannels()
    {
        return $this->channels()->wherePivot('role', 'admin');
    }

    /**
     * 送信したダイレクトメッセージとのリレーション
     */
    public function sentDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    /**
     * 受信したダイレクトメッセージとのリレーション
     */
    public function receivedDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'receiver_id');
    }

    /**
     * 特定のユーザーとのダイレクトメッセージ履歴を取得
     */
    public function directMessagesWith(int $otherUserId)
    {
        return DirectMessage::where(function ($query) use ($otherUserId) {
            $query->where('sender_id', $this->id)
                  ->where('receiver_id', $otherUserId);
        })->orWhere(function ($query) use ($otherUserId) {
            $query->where('sender_id', $otherUserId)
                  ->where('receiver_id', $this->id);
        })->with(['sender', 'receiver'])
          ->orderBy('created_at', 'desc');
    }

    /**
     * 未読のダイレクトメッセージ数を取得
     */
    public function unreadDirectMessagesCount(): int
    {
        return $this->receivedDirectMessages()
            ->whereNull('read_at')
            ->count();
    }

    /**
     * ダイレクトメッセージの相手一覧を取得
     */
    public function directMessagePartners()
    {
        $sentPartners = $this->sentDirectMessages()
            ->select('receiver_id')
            ->distinct()
            ->pluck('receiver_id');

        $receivedPartners = $this->receivedDirectMessages()
            ->select('sender_id')
            ->distinct()
            ->pluck('sender_id');

        $partnerIds = $sentPartners->merge($receivedPartners)->unique();

        return User::whereIn('id', $partnerIds)->get();
    }
}

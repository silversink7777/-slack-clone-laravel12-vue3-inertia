<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DirectMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_direct_messages';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * 送信者とのリレーション
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 受信者とのリレーション
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * メッセージが既読かどうかをチェック
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * メッセージを既読にする
     */
    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    /**
     * ファイルが添付されているかチェック
     */
    public function hasFile(): bool
    {
        return !is_null($this->file_path);
    }

    /**
     * ファイルのURLを取得
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }
        return asset('storage/' . $this->file_path);
    }
}

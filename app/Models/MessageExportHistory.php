<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageExportHistory extends Model
{
    use HasFactory;

    protected $table = 'tbl_message_export_histories';

    protected $fillable = [
        'user_id',
        'channel_id',
        'partner_id',
        'format',
        'filename',
        'message_count',
        'file_size',
        'exported_at',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'message_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * エクスポートを実行したユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * エクスポート対象のチャンネル
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * エクスポート対象のダイレクトメッセージパートナー
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    /**
     * ファイルサイズを人間が読みやすい形式で取得
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '不明';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * エクスポートタイプを取得
     */
    public function getExportTypeAttribute(): string
    {
        if ($this->channel_id) {
            return 'チャンネル';
        } elseif ($this->partner_id) {
            return 'ダイレクトメッセージ';
        }
        return '不明';
    }

    /**
     * エクスポート対象名を取得
     */
    public function getExportTargetNameAttribute(): string
    {
        if ($this->channel_id && $this->channel) {
            return $this->channel->name;
        } elseif ($this->partner_id && $this->partner) {
            return $this->partner->name;
        }
        return '不明';
    }
} 
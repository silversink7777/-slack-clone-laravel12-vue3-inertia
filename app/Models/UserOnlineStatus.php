<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOnlineStatus extends Model
{
    protected $table = 'tbl_user_online_status';
    protected $fillable = [
        'user_id', 'online', 'last_active_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * 允许批量写入的字段
     */
    protected $fillable = [
        'user_id',      
        'item_name',
        'message',
        'expiry_date',
        'status',
    ];

    /**
     * 每条通知属于一个用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

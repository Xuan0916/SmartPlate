<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_name',
        'category',
        'quantity',
        'unit',
        'expiry_date',
        'pickup_location',
        'pickup_duration',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

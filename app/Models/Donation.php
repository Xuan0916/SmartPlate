<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'user_id',
        'item_name',
        'category',
        'quantity',
        'unit',
        'expiry_date',
        'pickup_location',
        'pickup_duration',
        'status',
        'inventory_item_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

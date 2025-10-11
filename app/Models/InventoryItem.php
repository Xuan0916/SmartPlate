<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items'; 

    protected $fillable = [
        'name',
        'quantity',
        'unit',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Waste extends Model
{
    use HasFactory;

    // Add all columns you want to mass assign
    protected $fillable = [
        'user_id',
        'inventory_item_id',
        'item_name',
        'category',
        'quantity_wasted',
        'unit',
        'date_expired',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'item_name',
        'quantity',
        'unit',
        'expiry_date',
        'pickup_location',
        'pickup_duration',
    ];

    /**
     * Cast expiry_date as a Carbon (date) object.
     */
    protected $casts = [
        'expiry_date' => 'date',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MealIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_id',
        'inventory_item_id',
        'quantity_used'
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

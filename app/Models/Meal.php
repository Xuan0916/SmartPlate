<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'date',
        'meal_type',
        'recipe_name',
        'notes'
    ];

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function ingredients()
    {
        return $this->hasMany(MealIngredient::class);
    }
}

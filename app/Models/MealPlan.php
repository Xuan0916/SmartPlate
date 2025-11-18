<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'week_start'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meals()
    {
        return $this->hasMany(Meal::class);
    }
}

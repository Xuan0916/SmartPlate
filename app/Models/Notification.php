<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_name',
        'message',
        'expiry_date',
        'status',
        'target_type',
        'target_id',
    ];

    // Make 'link' available to Blade
    protected $appends = ['link'];

    public function getLinkAttribute()
    {
        // No target → default go to inventory
        if (!$this->target_type || !$this->target_id) {
            return route('inventory.index');
        }

        switch ($this->target_type) {
            case 'donation':
                return route('donation.index');

            case 'mealplan':
                return route('mealplans.show', $this->target_id);

            default:
                return route('inventory.index');
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

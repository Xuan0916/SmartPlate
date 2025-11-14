<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ✅ 新增（可选，但推荐）

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes; // ✅ 启用软删除特性

    // 数据表名
    protected $table = 'inventory_items';

    // ✅ 允许批量写入的字段（防止 MassAssignmentException）
    protected $fillable = ['name', 'category', 'quantity','original_quantity','reserved_quantity', 'unit', 'expiry_date','user_id','status',];


    // ✅ 自动类型转换
    protected $casts = [
        'expiry_date' => 'date',
    ];

    // ✅ 默认日期格式（可选）
    protected $dates = [
        'expiry_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ 工具函数：检查库存中是否已有相同物品
    public static function existsByName(string $name): bool
    {
        return self::where('name', $name)->exists();
    }

    // ✅ 工具函数：从 donation 返回时可安全创建或恢复
    public static function restoreOrCreate(array $data)
    {
        // 优先检查是否存在被软删除的同名物品
        $existing = self::withTrashed()->where('name', $data['name'])->first();

        if ($existing) {
            // 如果存在软删除的旧记录，就恢复它
            $existing->restore();
            $existing->update($data);
            return $existing;
        }

        // 否则新建一条
        return self::create($data);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function mealIngredients()
    {
        return $this->hasMany(MealIngredient::class, 'inventory_item_id');
    }

}

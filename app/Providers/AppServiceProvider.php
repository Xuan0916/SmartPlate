<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $hasUnread = false;

        if (Schema::hasTable('notifications')) {
            try {
                // 如果你的表有 "status" 字段，未读用 'new'
                if (Schema::hasColumn('notifications', 'status')) {
                    $query = Notification::query()->where('status', 'new');
                }
                // 或者如果你的表用 "is_read" 布尔字段
                elseif (Schema::hasColumn('notifications', 'is_read')) {
                    $query = Notification::query()->where('is_read', false);
                } else {
                    $query = null;
                }

                // 如果是按用户维度，请加上这行：
                if ($query && auth()->check() && Schema::hasColumn('notifications', 'user_id')) {
                    $query->where('user_id', auth()->id());
                }

                $hasUnread = $query ? $query->exists() : false;
            } catch (\Throwable $e) {
                $hasUnread = false;
            }
        }

        View::share('hasUnread', $hasUnread);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ 全局共享未读通知数量（所有视图都可访问）
        View::composer('*', function ($view) {
            $unreadCount = 0; // 默认值

            if (Schema::hasTable('notifications')) {
                try {
                    if (Auth::check()) {
                        // 如果表结构兼容 status / is_read / user_id 等字段
                        $query = Notification::query();

                        if (Schema::hasColumn('notifications', 'status')) {
                            $query->where('status', 'new');
                        } elseif (Schema::hasColumn('notifications', 'is_read')) {
                            $query->where('is_read', false);
                        }

                        if (Schema::hasColumn('notifications', 'user_id')) {
                            $query->where('user_id', Auth::id());
                        }

                        $unreadCount = $query->count();
                    }
                } catch (\Throwable $e) {
                    $unreadCount = 0;
                }
            }

            // ✅ 将变量注入所有 Blade 页面
            $view->with('unreadCount', $unreadCount);
        });
    }
}

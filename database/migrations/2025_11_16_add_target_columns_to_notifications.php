<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('target_type')->nullable()->after('message'); // inventory / donation / mealplan
            $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_id']);
        });
    }
};

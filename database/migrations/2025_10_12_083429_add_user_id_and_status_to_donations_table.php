<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // ✅ Add user_id column
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->after('id');

            // ✅ Add status column
            $table->string('status')
                  ->default('available')
                  ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Drop both columns when rolling back
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'status']);
        });
    }
};

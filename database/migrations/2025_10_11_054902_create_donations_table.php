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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->default('pcs');
            $table->date('expiry_date')->nullable();
            $table->string('pickup_location');
            $table->string('pickup_duration');
            $table->timestamps();

            // Optional index for quick lookups by expiry or name
            $table->index(['item_name', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};

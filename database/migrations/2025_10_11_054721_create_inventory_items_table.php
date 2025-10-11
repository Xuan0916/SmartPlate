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
        Schema::create('inventory_items', function (Blueprint $table) {

            $table->id();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit')->default('pcs');
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            // Optional index for faster sorting by expiry date
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

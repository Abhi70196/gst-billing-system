<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bill_of_supply_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_supply_id')
                  ->constrained('bills_of_supply')
                  ->onDelete('cascade');
            $table->string('product_name');
            $table->string('hsn_sac', 8)->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_of_supply_items');
    }
};
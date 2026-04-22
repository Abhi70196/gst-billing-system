<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bills_of_supply', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->date('date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');
            $table->string('supply_type')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills_of_supply');
    }
};

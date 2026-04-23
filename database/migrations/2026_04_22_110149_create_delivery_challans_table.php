<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_number')->unique();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->date('date');
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_state')->nullable();
            $table->string('delivery_state_code', 2)->nullable();
            $table->enum('purpose', ['supply', 'job_work', 'return', 'line_sales', 'others'])->default('supply');
            $table->enum('status', ['draft', 'dispatched', 'delivered', 'cancelled'])->default('draft');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challans');
    }
};
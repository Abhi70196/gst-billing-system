<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('frequency', [
                'weekly','fortnightly','monthly',
                'quarterly','half_yearly','annually'
            ]);
            $table->date('next_billing_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active','paused','cancelled'])->default('active');
            $table->json('item_templates');
            $table->text('notes')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id', 'created_by', 'frequency',
                'next_billing_date', 'end_date', 'status',
                'item_templates', 'notes', 'deleted_at'
            ]);
        });
    }
};
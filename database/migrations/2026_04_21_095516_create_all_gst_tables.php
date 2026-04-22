<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // // --- USERS TABLE ---
        // Schema::create('users', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name');
        //     $table->string('email')->unique();
        //     $table->string('password');
        //     $table->enum('role', ['admin', 'staff'])->default('staff');
        //     $table->boolean('is_active')->default(true);
        //     $table->timestamps();
        // });

        // --- CUSTOMERS TABLE ---
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gstin', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 15)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('state_code', 2)->nullable();
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->boolean('reminder_opt_out')->default(false);
            $table->boolean('is_composition_dealer')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // --- PRODUCTS TABLE ---
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hsn_sac_code', 8)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('gst_rate', 5, 2)->default(18);
            $table->decimal('cess_rate', 8, 2)->default(0);
            $table->string('unit')->default('NOS');
            $table->boolean('is_exempt')->default(false);
            $table->boolean('is_service')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // --- INVOICES TABLE ---
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique()->nullable();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft','finalised','paid','partial','cancelled'])->default('draft');
            $table->string('place_of_supply', 2);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('cgst_total', 15, 2)->default(0);
            $table->decimal('sgst_total', 15, 2)->default(0);
            $table->decimal('igst_total', 15, 2)->default(0);
            $table->decimal('cess_total', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_export')->default(false);
            $table->boolean('is_sez')->default(false);
            $table->boolean('reverse_charge')->default(false);
            $table->string('invoice_template')->default('template_classic');
            $table->string('document_hash', 64)->nullable();
            $table->timestamp('finalised_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // --- INVOICE ITEMS TABLE ---
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('hsn_sac_code', 8)->nullable();
            $table->decimal('qty', 10, 3);
            $table->string('unit')->default('NOS');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('taxable_value', 15, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('cess_rate', 8, 2)->default(0);
            $table->decimal('cess_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });

        // --- PAYMENTS TABLE ---
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_mode', ['cash','upi','bank','cheque','other']);
            $table->date('payment_date');
            $table->string('reference_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // --- ACTIVITY LOGS TABLE ---
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // --- COMPANY SETTINGS TABLE ---
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
        // Schema::dropIfExists('users');
    }
};

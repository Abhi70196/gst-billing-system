<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_number')->unique();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->date('date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'converted', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('converted_invoice_id')->nullable();
            $table->foreign('converted_invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('set null');
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};

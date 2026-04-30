<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BulkInvoiceController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\InvoiceEmailController;

// ── PUBLIC ROUTES ──
Route::middleware(['throttle:5,1'])
    ->post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')
    ->post('/auth/logout', [AuthController::class, 'logout']);

// ── PROTECTED ROUTES ──
Route::middleware(['auth:sanctum'])->group(function () {

    // ── CUSTOMERS ──
    Route::apiResource('/customers', CustomerController::class);

    // ── PRODUCTS ──
    Route::apiResource('/products', ProductController::class);

    // ── INVOICES ──
    Route::get('/invoices',                    [InvoiceController::class, 'index']);
    Route::post('/invoices',                   [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}',          [InvoiceController::class, 'show']);
    Route::put('/invoices/{invoice}',          [InvoiceController::class, 'update']);
    Route::post('/invoices/{invoice}/finalise',[InvoiceController::class, 'finalise']);
    Route::get('/invoices/{invoice}/pdf',      [InvoiceController::class, 'pdf']);
    Route::post('/invoices/{invoice}/payments',[PaymentController::class, 'store']);

    // Email routes
    Route::post('/invoices/{invoice}/send-email', [InvoiceEmailController::class, 'sendInvoice']);
    Route::post('/invoices/{invoice}/send-reminder', [InvoiceEmailController::class, 'sendReminder']);
    Route::post('/invoices/{invoice}/send-payment-confirmation', [InvoiceEmailController::class, 'sendPaymentConfirmation']);

    // ── BULK OPERATIONS ──
    Route::post('/invoices/bulk-pdf',    [BulkInvoiceController::class, 'bulkPdf']);
    Route::post('/invoices/bulk-status', [BulkInvoiceController::class, 'bulkStatus']);
    Route::post('/invoices/bulk-export', [BulkInvoiceController::class, 'bulkExport']);

    // ── RECURRING INVOICES ──
    Route::apiResource('/recurring-invoices', RecurringInvoiceController::class);
    Route::patch('/recurring-invoices/{recurringInvoice}/pause',
        [RecurringInvoiceController::class, 'pause']);

    // ── MEMBER 2 ROUTES ──
    // Vendors
    Route::apiResource('/vendors', \App\Http\Controllers\VendorController::class);

    // Purchase Bills
    Route::apiResource('/purchase-bills', \App\Http\Controllers\PurchaseBillController::class);
    Route::post('/purchase-bills/{bill}/payments', [\App\Http\Controllers\PurchaseBillController::class, 'recordPayment']);

    // Proforma Invoices
    Route::apiResource('/proforma-invoices', \App\Http\Controllers\ProformaInvoiceController::class);
    Route::post('/proforma-invoices/{proforma}/convert', [\App\Http\Controllers\ProformaInvoiceController::class, 'convert']);

    // Bills of Supply
    Route::apiResource('/bills-of-supply', \App\Http\Controllers\BillOfSupplyController::class);

    // Delivery Challans
    Route::apiResource('/delivery-challans', \App\Http\Controllers\DeliveryChallanController::class);
    Route::post('/delivery-challans/{challan}/convert', [\App\Http\Controllers\DeliveryChallanController::class, 'convert']);

    // Credit Notes
    Route::get('/credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'index']);
    Route::post('/invoices/{invoice}/credit-notes', [\App\Http\Controllers\CreditNoteController::class, 'store']);

    // Debit Notes
    Route::get('/debit-notes', [\App\Http\Controllers\DebitNoteController::class, 'index']);
    Route::post('/invoices/{invoice}/debit-notes', [\App\Http\Controllers\DebitNoteController::class, 'store']);

    // ── DASHBOARD ──
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // ── REPORTS (Admin only) ──
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports/gst-summary',
            [ReportController::class, 'gstSummary']);
        Route::get('/reports/gstr-3b-summary',
            [ReportController::class, 'gstr3bSummary']);
        Route::get('/reports/pending-payments',
            [ReportController::class, 'pendingPayments']);
        Route::get('/reports/itc-summary',
            [ReportController::class, 'itcSummary']);
        Route::get('/reports/customer-ledger',
            [ReportController::class, 'customerLedger']);
        Route::get('/reports/sales',
            [ReportController::class, 'sales']);
    });

});
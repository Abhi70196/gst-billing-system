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

    // ── BULK OPERATIONS ──
    Route::post('/invoices/bulk-pdf',    [BulkInvoiceController::class, 'bulkPdf']);
    Route::post('/invoices/bulk-status', [BulkInvoiceController::class, 'bulkStatus']);
    Route::post('/invoices/bulk-export', [BulkInvoiceController::class, 'bulkExport']);

    // ── RECURRING INVOICES ──
    Route::apiResource('/recurring-invoices', RecurringInvoiceController::class);
    Route::patch('/recurring-invoices/{recurringInvoice}/pause',
        [RecurringInvoiceController::class, 'pause']);

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
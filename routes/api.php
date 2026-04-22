<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;

// PUBLIC ROUTE - Login
Route::middleware(['throttle:5,1'])->post('/auth/login', [AuthController::class, 'login']);

// PROTECTED ROUTES
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Customers
    Route::apiResource('/customers', CustomerController::class);

    // Products
    Route::apiResource('/products', ProductController::class);

    // Invoices
    Route::get('/invoices',                    [InvoiceController::class, 'index']);
    Route::post('/invoices',                   [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}',          [InvoiceController::class, 'show']);
    Route::post('/invoices/{invoice}/finalise',[InvoiceController::class, 'finalise']);

    // Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
});
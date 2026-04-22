<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,upi,bank,cheque,other',
            'payment_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'remarks'      => 'nullable|string',
        ]);

        $payment = Payment::create([
            'invoice_id'   => $invoice->id,
            'customer_id'  => $invoice->customer_id,
            'amount'       => $request->amount,
            'payment_mode' => $request->payment_mode,
            'payment_date' => $request->payment_date,
            'reference_no' => $request->reference_no,
            'remarks'      => $request->remarks,
        ]);

        // Update invoice status
        $totalPaid = Payment::where('invoice_id', $invoice->id)->sum('amount');
        if ($totalPaid >= $invoice->total_amount) {
            $invoice->status = 'paid';
        } else {
            $invoice->status = 'partial';
        }
        $invoice->save();

        return response()->json($payment, 201);
    }
}
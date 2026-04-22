<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    /** GET /recurring-invoices */
    public function index()
    {
        $recurring = RecurringInvoice::with('customer')
            ->where('created_by', auth()->id())
            ->get();

        return response()->json($recurring);
    }

    /** POST /recurring-invoices */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'       => 'required|exists:customers,id',
            'frequency'         => 'required|in:weekly,fortnightly,monthly,quarterly,half_yearly,annually',
            'next_billing_date' => 'required|date|after:today',
            'end_date'          => 'nullable|date|after:next_billing_date',
            'item_templates'    => 'required|array|min:1',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $recurring = RecurringInvoice::create([
            'customer_id'       => $request->customer_id,
            'created_by'        => auth()->id(),
            'frequency'         => $request->frequency,
            'next_billing_date' => $request->next_billing_date,
            'end_date'          => $request->end_date,
            'status'            => 'active',
            'item_templates'    => json_encode($request->item_templates),
            'notes'             => $request->notes,
        ]);

        return response()->json($recurring, 201);
    }

    /** GET /recurring-invoices/{id} */
    public function show(RecurringInvoice $recurringInvoice)
    {
        return response()->json(
            $recurringInvoice->load('customer')
        );
    }

    /** PUT /recurring-invoices/{id} */
    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        $request->validate([
            'frequency'         => 'in:weekly,fortnightly,monthly,quarterly,half_yearly,annually',
            'next_billing_date' => 'date',
            'end_date'          => 'nullable|date',
            'item_templates'    => 'array|min:1',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $recurringInvoice->update($request->all());

        return response()->json($recurringInvoice);
    }

    /** DELETE /recurring-invoices/{id} */
    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->delete();
        return response()->json(['message' => 'Deleted successfully.']);
    }

    /** PATCH /recurring-invoices/{id}/pause */
    public function pause(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->status = 
            $recurringInvoice->status === 'active' ? 'paused' : 'active';
        $recurringInvoice->save();

        return response()->json([
            'message' => 'Status updated.',
            'status'  => $recurringInvoice->status
        ]);
    }
}
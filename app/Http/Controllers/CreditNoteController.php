<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Services\CreditNoteNumberService;
use App\Services\GstCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function __construct(
        protected CreditNoteNumberService $numberService,
        protected GstCalculationService $gstService
    ) {}

    public function index()
    {
        return response()->json(
            CreditNote::with(['customer', 'invoice', 'items'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id'  => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'date'        => 'required|date',
            'reason'      => 'nullable|string',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.product_name'   => 'required|string',
            'items.*.quantity'       => 'required|numeric|min:0',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.gst_rate'       => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0|max:100',
            'items.*.hsn_sac'        => 'nullable|string',
            'items.*.description'    => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;

            $processedItems = [];
            foreach ($data['items'] as $item) {
                $discount      = $item['discount'] ?? 0;
                $taxableAmount = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
                $gst           = $this->gstService->calculate($taxableAmount, $item['gst_rate']);

                $processedItems[] = array_merge($item, [
                    'taxable_amount' => $taxableAmount,
                    'cgst_rate'      => $gst['cgst_rate'],
                    'sgst_rate'      => $gst['sgst_rate'],
                    'igst_rate'      => $gst['igst_rate'],
                    'cgst_amount'    => $gst['cgst_amount'],
                    'sgst_amount'    => $gst['sgst_amount'],
                    'igst_amount'    => $gst['igst_amount'],
                    'total_amount'   => $taxableAmount + $gst['total_gst'],
                ]);

                $subtotal   += $taxableAmount;
                $totalCgst  += $gst['cgst_amount'];
                $totalSgst  += $gst['sgst_amount'];
                $totalIgst  += $gst['igst_amount'];
            }

            $creditNote = CreditNote::create([
                'credit_note_number' => $this->numberService->generate(),
                'invoice_id'         => $data['invoice_id'],
                'customer_id'        => $data['customer_id'],
                'date'               => $data['date'],
                'reason'             => $data['reason'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'subtotal'           => $subtotal,
                'cgst_amount'        => $totalCgst,
                'sgst_amount'        => $totalSgst,
                'igst_amount'        => $totalIgst,
                'total_amount'       => $subtotal + $totalCgst + $totalSgst + $totalIgst,
                'status'             => 'draft',
            ]);

            $creditNote->items()->createMany($processedItems);

            DB::commit();
            return response()->json($creditNote->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(CreditNote $creditNote)
    {
        return response()->json($creditNote->load(['customer', 'invoice', 'items']));
    }

    public function update(Request $request, CreditNote $creditNote)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,issued,cancelled',
            'reason' => 'nullable|string',
            'notes'  => 'nullable|string',
        ]);

        $creditNote->update($data);
        return response()->json($creditNote);
    }

    public function destroy(CreditNote $creditNote)
    {
        $creditNote->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Credit note cancelled']);
    }
}
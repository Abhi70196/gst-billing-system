<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use App\Services\GstCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceController extends Controller
{
    public function __construct(protected GstCalculationService $gstService) {}

    private function generateNumber(): string
    {
        $last = ProformaInvoice::latest('id')->first();
        $next = $last ? ((int) substr($last->proforma_number, -4)) + 1 : 1;
        return 'PI-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        return response()->json(
            ProformaInvoice::with(['customer', 'items'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'       => 'required|exists:customers,id',
            'date'              => 'required|date',
            'valid_until'       => 'nullable|date|after_or_equal:date',
            'terms_conditions'  => 'nullable|string',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.gst_rate'     => 'required|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0|max:100',
            'items.*.hsn_sac'      => 'nullable|string',
            'items.*.description'  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0; $totalCgst = 0; $totalSgst = 0; $totalIgst = 0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $discount      = $item['discount'] ?? 0;
                $taxableAmount = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
               $gst = $this->gstService->calculate($taxableAmount, $item['gst_rate'], 0, 'intra');

                $processedItems[] = array_merge($item, [
                    'taxable_amount' => $taxableAmount,
                    'cgst_rate'      => $gst['cgst_rate'],
                    'sgst_rate'      => $gst['sgst_rate'],
                    'igst_rate'      => $gst['igst_rate'],
                    'cgst_amount'    => $gst['cgst_amount'],
                    'sgst_amount'    => $gst['sgst_amount'],
                    'igst_amount'    => $gst['igst_amount'],
                    'total_amount'   => $gst['total'],
                ]);

                $subtotal  += $taxableAmount;
                $totalCgst += $gst['cgst_amount'];
                $totalSgst += $gst['sgst_amount'];
                $totalIgst += $gst['igst_amount'];     
       }

            $proforma = ProformaInvoice::create([
                'proforma_number'   => $this->generateNumber(),
                'customer_id'       => $data['customer_id'],
                'date'              => $data['date'],
                'valid_until'       => $data['valid_until'] ?? null,
                'terms_conditions'  => $data['terms_conditions'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'subtotal'          => $subtotal,
                'cgst_amount'       => $totalCgst,
                'sgst_amount'       => $totalSgst,
                'igst_amount'       => $totalIgst,
                'total_amount'      => $subtotal + $totalCgst + $totalSgst + $totalIgst,
                'status'            => 'draft',
            ]);

            $proforma->items()->createMany($processedItems);

            DB::commit();
            return response()->json($proforma->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(ProformaInvoice $proformaInvoice)
    {
        return response()->json($proformaInvoice->load(['customer', 'items']));
    }

    public function update(Request $request, ProformaInvoice $proformaInvoice)
    {
        $data = $request->validate([
            'status'               => 'sometimes|in:draft,sent,converted,cancelled',
            'valid_until'          => 'nullable|date',
            'terms_conditions'     => 'nullable|string',
            'notes'                => 'nullable|string',
            'converted_invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $proformaInvoice->update($data);
        return response()->json($proformaInvoice);
    }

    public function destroy(ProformaInvoice $proformaInvoice)
    {
        $proformaInvoice->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Proforma invoice cancelled']);
    }
}
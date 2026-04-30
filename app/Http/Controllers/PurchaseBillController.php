<?php

namespace App\Http\Controllers;

use App\Models\PurchaseBill;
use App\Services\GstCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseBillController extends Controller
{
    public function __construct(protected GstCalculationService $gstService) {}

    private function generateNumber(): string
    {
        $last = PurchaseBill::latest('id')->first();
        $next = $last ? ((int) substr($last->bill_number, -4)) + 1 : 1;
        return 'PB-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        $bills = PurchaseBill::with(['vendor', 'items.product'])
            ->latest()
            ->get();
        return response()->json($bills);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id'            => 'required|exists:vendors,id',
            'vendor_bill_number'   => 'nullable|string',
            'date'                 => 'required|date',
            'due_date'             => 'nullable|date|after_or_equal:date',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
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
            $subtotal  = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $discount      = $item['discount'] ?? 0;
                $taxableAmount = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);

                $gst = $this->gstService->calculate(
                    $taxableAmount,
                    $item['gst_rate'],
                    0,
                    'intra'
                );

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

            $total = $subtotal + $totalCgst + $totalSgst + $totalIgst;

            $bill = PurchaseBill::create([
                'bill_number'        => $this->generateNumber(),
                'vendor_bill_number' => $data['vendor_bill_number'] ?? null,
                'vendor_id'          => $data['vendor_id'],
                'date'               => $data['date'],
                'due_date'           => $data['due_date'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'subtotal'           => $subtotal,
                'cgst_amount'        => $totalCgst,
                'sgst_amount'        => $totalSgst,
                'igst_amount'        => $totalIgst,
                'total_amount'       => $total,
                'paid_amount'        => 0,
                'balance_due'        => $total,
                'status'             => 'unpaid',
            ]);

            $bill->items()->createMany($processedItems);

            DB::commit();
            return response()->json($bill->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(PurchaseBill $purchaseBill)
    {
        return response()->json($purchaseBill->load(['vendor', 'items']));
    }

    public function update(Request $request, PurchaseBill $purchaseBill)
    {
        $data = $request->validate([
            'status'      => 'sometimes|in:unpaid,partial,paid,cancelled',
            'paid_amount' => 'sometimes|numeric|min:0',
            'due_date'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        if (isset($data['paid_amount'])) {
            $data['balance_due'] = $purchaseBill->total_amount - $data['paid_amount'];
            if ($data['balance_due'] <= 0) {
                $data['status']    = 'paid';
                $data['balance_due'] = 0;
            } elseif ($data['paid_amount'] > 0) {
                $data['status'] = 'partial';
            }
        }

        $purchaseBill->update($data);
        return response()->json($purchaseBill);
    }

    public function destroy(PurchaseBill $purchaseBill)
    {
        $purchaseBill->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Purchase bill cancelled']);
    }

    public function recordPayment(Request $request, PurchaseBill $purchaseBill)
    {
        $data = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,upi,bank,cheque,other',
            'payment_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'remarks'      => 'nullable|string',
        ]);

        $newPaid = $purchaseBill->paid_amount + $data['amount'];
        $balance = $purchaseBill->total_amount - $newPaid;

        $purchaseBill->update([
            'paid_amount' => $newPaid,
            'balance_due' => max(0, $balance),
            'status'      => $balance <= 0 ? 'paid' : 'partial',
        ]);

        return response()->json([
            'message'     => 'Payment recorded successfully.',
            'paid_amount' => $newPaid,
            'balance_due' => max(0, $balance),
            'status'      => $purchaseBill->status,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\BillOfSupply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillOfSupplyController extends Controller
{
    private function generateNumber(): string
    {
        $last = BillOfSupply::latest('id')->first();
        $next = $last ? ((int) substr($last->bill_number, -4)) + 1 : 1;
        return 'BOS-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        return response()->json(
            BillOfSupply::with(['customer', 'items'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'date'             => 'required|date',
            'supply_type'      => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'notes'            => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0|max:100',
            'items.*.hsn_sac'      => 'nullable|string',
            'items.*.description'  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $discount    = $item['discount'] ?? 0;
                $itemTotal   = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
                $totalAmount += $itemTotal;

               $processedItems[] = [
    'product_name' => $item['product_name'],
    'hsn_sac'      => $item['hsn_sac'] ?? null,
    'description'  => $item['description'] ?? null,
    'quantity'     => $item['quantity'],
    'unit'         => $item['unit'] ?? null,
    'unit_price'   => $item['unit_price'],
    'discount'     => $item['discount'] ?? 0,
    'total_amount' => round($itemTotal, 2),
];
            }

            $bill = BillOfSupply::create([
                'bill_number'      => $this->generateNumber(),
                'customer_id'      => $data['customer_id'],
                'date'             => $data['date'],
                'supply_type'      => $data['supply_type'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'total_amount'     => $totalAmount,
                'status'           => 'draft',
            ]);

            $bill->items()->createMany($processedItems);

            DB::commit();
            return response()->json($bill->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(BillOfSupply $billOfSupply)
    {
        return response()->json($billOfSupply->load(['customer', 'items']));
    }

    public function update(Request $request, BillOfSupply $billOfSupply)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:draft,issued,cancelled',
            'notes'  => 'nullable|string',
        ]);

        $billOfSupply->update($data);
        return response()->json($billOfSupply);
    }

    public function destroy(BillOfSupply $billOfSupply)
    {
        $billOfSupply->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Bill of supply cancelled']);
    }
}
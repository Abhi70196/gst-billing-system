<?php

namespace App\Http\Controllers;

use App\Models\DeliveryChallan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryChallanController extends Controller
{
    private function generateNumber(): string
    {
        $last = DeliveryChallan::latest('id')->first();
        $next = $last ? ((int) substr($last->challan_number, -4)) + 1 : 1;
        return 'DC-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        return response()->json(
            DeliveryChallan::with(['customer', 'items'])->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'date'                 => 'required|date',
            'vehicle_number'       => 'nullable|string',
            'driver_name'          => 'nullable|string',
            'delivery_address'     => 'nullable|string',
            'delivery_state'       => 'nullable|string',
            'delivery_state_code'  => 'nullable|string|max:2',
            'purpose'              => 'nullable|in:supply,job_work,return,line_sales,others',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.hsn_sac'      => 'nullable|string',
            'items.*.description'  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $processedItems[] = array_merge($item, [
                    'total_amount' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $challan = DeliveryChallan::create([
                'challan_number'       => $this->generateNumber(),
                'customer_id'          => $data['customer_id'],
                'date'                 => $data['date'],
                'vehicle_number'       => $data['vehicle_number'] ?? null,
                'driver_name'          => $data['driver_name'] ?? null,
                'delivery_address'     => $data['delivery_address'] ?? null,
                'delivery_state'       => $data['delivery_state'] ?? null,
                'delivery_state_code'  => $data['delivery_state_code'] ?? null,
                'purpose'              => $data['purpose'] ?? 'supply',
                'notes'                => $data['notes'] ?? null,
                'status'               => 'draft',
            ]);

            $challan->items()->createMany($processedItems);

            DB::commit();
            return response()->json($challan->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(DeliveryChallan $deliveryChallan)
    {
        return response()->json($deliveryChallan->load(['customer', 'items']));
    }

    public function update(Request $request, DeliveryChallan $deliveryChallan)
    {
        $data = $request->validate([
            'status'         => 'sometimes|in:draft,dispatched,delivered,cancelled',
            'vehicle_number' => 'nullable|string',
            'driver_name'    => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        if (isset($data['status'])) {
            if ($data['status'] === 'dispatched') {
                $data['dispatched_at'] = now();
            }
            if ($data['status'] === 'delivered') {
                $data['delivered_at'] = now();
            }
        }

        $deliveryChallan->update($data);
        return response()->json($deliveryChallan);
    }

    public function destroy(DeliveryChallan $deliveryChallan)
    {
        $deliveryChallan->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Delivery challan cancelled']);
    }
}
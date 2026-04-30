<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::withCount('purchaseBills')
            ->latest()
            ->get();
        return response()->json($vendors);
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'gstin'          => 'nullable|string|size:15|unique:vendors,gstin',
            'email'          => 'nullable|email',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'state'          => 'nullable|string',
            'state_code'     => 'nullable|string|max:2',
            'pan'            => 'nullable|string|size:10',
            'contact_person' => 'nullable|string|max:255',
        ]);

        $vendor = Vendor::create($data);
        return response()->json($vendor, 201);
    }

    public function show(Vendor $vendor)
    {
        return response()->json($vendor->load(['debitNotes', 'purchaseBills']));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'gstin'          => 'nullable|string|size:15|unique:vendors,gstin,' . $vendor->id,
            'email'          => 'nullable|email',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
            'state'          => 'nullable|string',
            'state_code'     => 'nullable|string|max:2',
            'pan'            => 'nullable|string|size:10',
            'contact_person' => 'nullable|string|max:255',
            'is_active'      => 'sometimes|boolean',
        ]);

        $vendor->update($data);
        return response()->json($vendor);
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->update(['is_active' => false]);
        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
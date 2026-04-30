<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('invoices')
            ->latest()
            ->get();
        return response()->json($customers);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string',
            'gstin'        => 'nullable|string|max:15',
            'email'        => 'nullable|email',
            'phone'        => 'nullable|string|max:15',
            'state_code'   => 'nullable|string|max:2',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($request->all());
        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->all());
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Customer deleted.']);
    }
}
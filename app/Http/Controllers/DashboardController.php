<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        // Sales summary for the month
        $sales = Invoice::whereBetween('invoice_date', [$start, $end])
            ->whereNotIn('status', ['draft','cancelled'])
            ->selectRaw('COUNT(*) as invoice_count,
                SUM(total_amount) as total_sales,
                SUM(cgst_total)   as total_cgst,
                SUM(sgst_total)   as total_sgst,
                SUM(igst_total)   as total_igst'
            )->first();

        // Pending collections
        $pending = Invoice::whereIn('status', ['finalised','partial'])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as amount')
            ->first();

        // ITC for the month
        $itc = PurchaseBill::whereBetween('bill_date', [$start, $end])
            ->where('itc_eligible', true)
            ->selectRaw('SUM(cgst_total + sgst_total + igst_total) as total_itc')
            ->first();

        // Top 5 customers by sales
        $topCustomers = Invoice::with('customer')
            ->whereBetween('invoice_date', [$start, $end])
            ->whereNotIn('status', ['draft','cancelled'])
            ->selectRaw('customer_id, SUM(total_amount) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)->get();

        return response()->json([
            'month'         => $month,
            'sales'         => $sales,
            'pending'       => $pending,
            'itc'           => $itc,
            'top_customers' => $topCustomers,
        ]);
    }
}
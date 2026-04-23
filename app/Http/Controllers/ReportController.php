<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Exports\GstSummaryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function gstSummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $data = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->selectRaw('
                SUM(subtotal)     as total_taxable,
                SUM(cgst_total)   as total_cgst,
                SUM(sgst_total)   as total_sgst,
                SUM(igst_total)   as total_igst,
                SUM(cess_total)   as total_cess,
                SUM(total_amount) as grand_total,
                COUNT(*)          as invoice_count
            ')->first();

        if ($request->get('format') === 'xlsx') {
            return Excel::download(
                new GstSummaryExport($data, $month),
                'gst_summary_' . $month . '.xlsx'
            );
        }

        return response()->json($data);
    }

    public function gstr3bSummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $outward = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('is_export', false)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->selectRaw('
                SUM(subtotal)    as taxable,
                SUM(cgst_total)  as cgst,
                SUM(sgst_total)  as sgst,
                SUM(igst_total)  as igst
            ')->first();

        $zeroRated = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('is_export', true)
            ->selectRaw('SUM(subtotal) as taxable')
            ->first();

        $rcm = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('reverse_charge', true)
            ->selectRaw('
                SUM(subtotal)   as taxable,
                SUM(igst_total) as igst
            ')->first();

        $itc = PurchaseBill::whereBetween('date', [$start, $end])
            ->selectRaw('
                SUM(cgst_amount) as cgst,
                SUM(sgst_amount) as sgst,
                SUM(igst_amount) as igst
            ')->first();

        return response()->json([
            'period'    => $month,
            '3_1_a'     => $outward,
            '3_1_b'     => $zeroRated,
            '3_1_d_rcm' => $rcm,
            '4_A_5_itc' => $itc,
        ]);
    }

    public function itcSummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $eligible = PurchaseBill::whereBetween('date', [$start, $end])
            ->selectRaw('
                SUM(cgst_amount) as cgst,
                SUM(sgst_amount) as sgst,
                SUM(igst_amount) as igst,
                SUM(total_amount) as total
            ')->first();

        $blocked = ['total' => 0];

        return response()->json([
            'eligible' => $eligible,
            'blocked'  => $blocked,
            'period'   => $month
        ]);
    }

    public function pendingPayments(Request $request)
    {
        $invoices = Invoice::whereIn('status', ['finalised', 'partial'])
            ->with('customer')
            ->selectRaw('*,
                DATEDIFF(CURDATE(), due_date) as days_overdue,
                CASE
                    WHEN DATEDIFF(CURDATE(), due_date) <= 0  THEN "0-Current"
                    WHEN DATEDIFF(CURDATE(), due_date) <= 30 THEN "1-30 days"
                    WHEN DATEDIFF(CURDATE(), due_date) <= 60 THEN "31-60 days"
                    ELSE "60+ days"
                END as aging_bucket'
            )->get();

        return response()->json($invoices);
    }

    public function sales(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $sales = Invoice::whereBetween('invoice_date', [$start, $end])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with('customer')
            ->get();

        return response()->json($sales);
    }

    public function customerLedger(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);

        $invoices = Invoice::where('customer_id', $request->customer_id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with('payments')
            ->orderBy('invoice_date')
            ->get();

        return response()->json($invoices);
    }
}
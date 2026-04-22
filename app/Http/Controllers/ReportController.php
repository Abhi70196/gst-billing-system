<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Models\Payment;
use App\Exports\GstSummaryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends Controller
{
    /** GET /reports/gst-summary?month=2026-04&format=xlsx */
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

    /** GET /reports/gstr-3b-summary?month=2026-04 */
    public function gstr3bSummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $outward = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('is_export', false)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'draft')
            ->selectRaw('SUM(subtotal) as taxable,
                         SUM(cgst_total) as cgst,
                         SUM(sgst_total) as sgst,
                         SUM(igst_total) as igst')
            ->first();

        $zeroRated = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('is_export', true)
            ->selectRaw('SUM(subtotal) as taxable')
            ->first();

        $rcm = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('reverse_charge', true)
            ->selectRaw('SUM(subtotal) as taxable,
                         SUM(igst_total) as igst')
            ->first();

        $itc = PurchaseBill::whereBetween('bill_date', [$start, $end])
            ->where('itc_eligible', true)
            ->selectRaw('SUM(cgst_total) as cgst,
                         SUM(sgst_total) as sgst,
                         SUM(igst_total) as igst')
            ->first();

        return response()->json([
            'period'    => $month,
            '3_1_a'     => $outward,
            '3_1_b'     => $zeroRated,
            '3_1_d_rcm' => $rcm,
            '4_A_5_itc' => $itc,
        ]);
    }

    /** GET /reports/itc-summary?month=2026-04 */
    public function itcSummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $eligible = PurchaseBill::whereBetween('bill_date', [$start, $end])
            ->where('itc_eligible', true)
            ->selectRaw('SUM(cgst_total) as cgst,
                         SUM(sgst_total) as sgst,
                         SUM(igst_total) as igst,
                         SUM(total_amount) as total')
            ->first();

        $blocked = PurchaseBill::whereBetween('bill_date', [$start, $end])
            ->where('itc_eligible', false)
            ->selectRaw('SUM(total_amount) as total')
            ->first();

        return response()->json([
            'eligible' => $eligible,
            'blocked'  => $blocked,
            'period'   => $month
        ]);
    }

    /** GET /reports/pending-payments */
    public function pendingPayments(Request $request)
    {
        $invoices = Invoice::whereIn('status', ['finalised','partial'])
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

    /** GET /reports/sales */
    public function sales(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $sales = Invoice::whereBetween('invoice_date', [$start, $end])
            ->whereNotIn('status', ['draft','cancelled'])
            ->with('customer')
            ->get();

        return response()->json($sales);
    }

    /** GET /reports/customer-ledger */
    public function customerLedger(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);

        $invoices = Invoice::where('customer_id', $request->customer_id)
            ->whereNotIn('status', ['draft','cancelled'])
            ->with('payments')
            ->orderBy('invoice_date')
            ->get();

        return response()->json($invoices);
    }
}
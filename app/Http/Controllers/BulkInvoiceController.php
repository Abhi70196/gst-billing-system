<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PdfGenerationService;
use Illuminate\Http\Request;
use ZipArchive;

class BulkInvoiceController extends Controller
{
    public function __construct(private PdfGenerationService $pdf) {}

    /** POST /invoices/bulk-pdf */
    public function bulkPdf(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:50',
            'ids.*' => 'exists:invoices,id'
        ]);

        $invoices = Invoice::with('items', 'customer')
            ->whereIn('id', $request->ids)
            ->where('created_by', auth()->id())
            ->get();

        $zipPath = storage_path('app/temp/bulk_' . now()->timestamp . '.zip');

        // Create temp directory if not exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        foreach ($invoices as $invoice) {
            $safeFilename = preg_replace(
                '/[^A-Za-z0-9_\-]/', '_',
                $invoice->invoice_number
            ) . '.pdf';
            $pdfContent = $this->pdf->generate(
                $invoice,
                $invoice->invoice_template
            );
            $zip->addFromString($safeFilename, $pdfContent);
        }

        $zip->close();

        return response()->download($zipPath, 'invoices_bulk.zip')
            ->deleteFileAfterSend(true);
    }

    /** POST /invoices/bulk-status */
    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'exists:invoices,id',
            'status' => 'required|in:paid,cancelled',
        ]);

        $updated = Invoice::whereIn('id', $request->ids)
            ->whereNotNull('finalised_at')
            ->update(['status' => $request->status]);

        return response()->json(['updated' => $updated]);
    }

    /** POST /invoices/bulk-export */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:invoices,id'
        ]);

        $invoices = Invoice::with('customer')
            ->whereIn('id', $request->ids)
            ->get(['id','invoice_number','invoice_date',
                   'total_amount','status','customer_id']);

        $csv = "Invoice No,Date,Customer,Amount,Status\n";
        foreach ($invoices as $inv) {
            $csv .= implode(',', [
                $inv->invoice_number,
                $inv->invoice_date,
                addslashes($inv->customer->name ?? ''),
                $inv->total_amount,
                $inv->status,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=invoices_export.csv',
        ]);
    }
}
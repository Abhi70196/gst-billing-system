<?php

namespace App\Http\Controllers;

use App\Services\PdfGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Services\GstCalculationService;
use App\Services\InvoiceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        private GstCalculationService $gst,
        private InvoiceNumberService  $numberService
    ) {}

    public function index()
    {
        return response()->json(
            Invoice::with('customer', 'items')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'invoice_date'         => 'required|date|before_or_equal:today',
            'due_date'             => 'nullable|date|after_or_equal:invoice_date',
            'is_export'            => 'boolean',
            'reverse_charge'       => 'boolean',
            'invoice_template'     => 'nullable|string',
            'notes'                => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.hsn_sac_code' => 'required|string|max:8',
            'items.*.qty'          => 'required|numeric|min:0.001',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount_pct' => 'nullable|numeric|min:0|max:100',
            'items.*.gst_rate'     => 'required|in:0,0.1,0.25,1.5,3,5,6,9,12,18,28',
            'items.*.cess_rate'    => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $customer = Customer::findOrFail($request->customer_id);

            $supplyType = $this->gst->determineSupplyType(
                config('company.state_code', '27'),
                $customer->state_code ?? '27',
                $request->is_export ?? false,
                false
            );

            $invoice = Invoice::create([
                'customer_id'      => $request->customer_id,
                'created_by'       => auth()->id(),
                'invoice_date'     => $request->invoice_date,
                'due_date'         => $request->due_date,
                'place_of_supply'  => $customer->state_code ?? '27',
                'status'           => 'draft',
                'is_export'        => $request->is_export ?? false,
                'reverse_charge'   => $request->reverse_charge ?? false,
                'invoice_template' => $request->invoice_template ?? 'template_classic',
                'notes'            => $request->notes,
            ]);

            $totals = [
                'subtotal' => 0, 'cgst' => 0,
                'sgst'     => 0, 'igst' => 0,
                'cess'     => 0, 'total' => 0
            ];

            foreach ($request->items as $item) {
                $taxable = round(
                    $item['qty'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100),
                    2
                );
                $calc = $this->gst->calculate(
                    $taxable,
                    $item['gst_rate'],
                    $item['cess_rate'] ?? 0,
                    $supplyType
                );

                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'description'  => $item['description'],
                    'hsn_sac_code' => $item['hsn_sac_code'],
                    'qty'          => $item['qty'],
                    'unit'         => $item['unit'] ?? 'NOS',
                    'unit_price'   => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'gst_rate'     => $item['gst_rate'],
                    ...$calc
                ]);

                $totals['subtotal'] += $calc['taxable_value'];
                $totals['cgst']     += $calc['cgst_amount'];
                $totals['sgst']     += $calc['sgst_amount'];
                $totals['igst']     += $calc['igst_amount'];
                $totals['cess']     += $calc['cess_amount'];
                $totals['total']    += $calc['total'];
            }

            $invoice->update([
                'subtotal'     => $totals['subtotal'],
                'cgst_total'   => $totals['cgst'],
                'sgst_total'   => $totals['sgst'],
                'igst_total'   => $totals['igst'],
                'cess_total'   => $totals['cess'],
                'total_amount' => $totals['total'],
            ]);

            return response()->json($invoice->load('items', 'customer'), 201);
        });
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load('items', 'customer', 'payments'));
    }

    public function finalise(Invoice $invoice)
    {
        if ($invoice->finalised_at) {
            return response()->json(['message' => 'Already finalised.'], 403);
        }

        $invoice->invoice_number = $this->numberService->next($invoice->invoice_date);
        $invoice->status         = 'finalised';
        $invoice->finalised_at   = now();
        $invoice->document_hash  = hash('sha256',
            $invoice->invoice_number . $invoice->total_amount . $invoice->invoice_date
        );
        $invoice->save();

        return response()->json($invoice);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->finalised_at) {
            return response()->json(['message' => 'Cannot update a finalised invoice.'], 403);
        }

        $request->validate([
            'notes'            => 'nullable|string|max:1000',
            'due_date'         => 'nullable|date',
            'invoice_template' => 'nullable|string',
        ]);

        $invoice->update($request->only(['notes', 'due_date', 'invoice_template']));
        return response()->json($invoice);
    }

    public function pdf(Invoice $invoice)
    {
        try {
            $invoice->load('items', 'customer', 'payments');

            $templateMap = [
                'template_classic' => 'pdf.invoice_classic',
                'template_modern'  => 'pdf.invoice_modern',
                'template_compact' => 'pdf.invoice_compact',
                'template_elegant' => 'pdf.invoice_elegant',
            ];

            $view = $templateMap[$invoice->invoice_template] 
                ?? 'pdf.invoice_classic';

            $pdf = Pdf::loadView($view, [
                'invoice' => $invoice,
                'company' => [
                    'name'       => config('company.name', 'Your Company'),
                    'address'    => config('company.address', 'Company Address'),
                    'gstin'      => config('company.gstin', 'GSTIN HERE'),
                    'phone'      => config('company.phone', ''),
                    'email'      => config('company.email', ''),
                    'state_code' => config('company.state_code', '27'),
                ]
            ]);

            $filename = 'invoice_' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
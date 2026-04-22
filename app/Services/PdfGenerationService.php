<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGenerationService
{
    private array $templateMap = [
        'template_classic' => 'pdf.invoice_classic',
        'template_modern'  => 'pdf.invoice_modern',
        'template_compact' => 'pdf.invoice_compact',
        'template_elegant' => 'pdf.invoice_elegant',
    ];

    public function generate(Invoice $invoice, string $templateId = 'template_classic'): string
    {
        $view = $this->templateMap[$templateId] 
            ?? $this->templateMap['template_classic'];

        return Pdf::loadView($view, [
            'invoice' => $invoice->load('items', 'customer', 'payments'),
            'company' => config('company'),
        ])->output();
    }

    public function stream(Invoice $invoice, string $templateId = 'template_classic')
    {
        $view = $this->templateMap[$templateId] 
            ?? $this->templateMap['template_classic'];

        return Pdf::loadView($view, [
            'invoice' => $invoice->load('items', 'customer', 'payments'),
            'company' => config('company'),
        ])->stream('invoice_' . $invoice->invoice_number . '.pdf');
    }
}
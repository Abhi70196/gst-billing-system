<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GstSummaryExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        private $data,
        private string $month
    ) {}

    public function array(): array
    {
        return [
            [
                $this->month,
                $this->data->invoice_count ?? 0,
                $this->data->total_taxable ?? 0,
                $this->data->total_cgst    ?? 0,
                $this->data->total_sgst    ?? 0,
                $this->data->total_igst    ?? 0,
                $this->data->total_cess    ?? 0,
                $this->data->grand_total   ?? 0,
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Month',
            'Invoice Count',
            'Total Taxable (₹)',
            'Total CGST (₹)',
            'Total SGST (₹)',
            'Total IGST (₹)',
            'Total CESS (₹)',
            'Grand Total (₹)',
        ];
    }

    public function title(): string
    {
        return 'GST Summary ' . $this->month;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style heading row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '2c3e50'],
                ],
            ],
        ];
    }
}
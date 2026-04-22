<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function next(string $invoiceDate): string
    {
        return DB::transaction(function () use ($invoiceDate) {
            $fy     = $this->getFy($invoiceDate);
            $prefix = 'INV-' . $fy . '-';

            $last = Invoice::where('invoice_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('invoice_number', 'desc')
                ->first();

            $seq = $last ? (int) substr($last->invoice_number, -3) + 1 : 1;

            return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
        });
    }

    private function getFy(string $date): string
    {
        $d    = \Carbon\Carbon::parse($date);
        $year = $d->month >= 4 ? $d->year : $d->year - 1;
        return $year . '-' . substr($year + 1, -2);
    }
}

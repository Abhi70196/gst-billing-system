<?php

namespace App\Services;

use App\Models\CreditNote;

class CreditNoteNumberService
{
    public function generate(): string
    {
        $last = CreditNote::latest('id')->first();
        $next = $last ? ((int) substr($last->credit_note_number, -4)) + 1 : 1;
        return 'CN-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
<?php

namespace App\Services;

use App\Models\DebitNote;

class DebitNoteNumberService
{
    public function generate(): string
    {
        $last = DebitNote::latest('id')->first();
        $next = $last ? ((int) substr($last->debit_note_number, -4)) + 1 : 1;
        return 'DN-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
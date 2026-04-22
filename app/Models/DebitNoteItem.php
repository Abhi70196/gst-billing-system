<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNoteItem extends Model
{
    protected $fillable = [
        'debit_note_id', 'product_name', 'hsn_sac', 'description',
        'quantity', 'unit', 'unit_price', 'discount', 'taxable_amount',
        'gst_rate', 'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'total_amount'
    ];

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }
}
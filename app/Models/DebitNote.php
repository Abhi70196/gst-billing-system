<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $fillable = [
        'debit_note_number', 'vendor_id', 'date', 'reason',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'status', 'notes'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(DebitNoteItem::class);
    }
}
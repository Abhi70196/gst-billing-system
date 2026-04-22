<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_number', 'invoice_id', 'customer_id', 'date',
        'reason', 'subtotal', 'cgst_amount', 'sgst_amount',
        'igst_amount', 'total_amount', 'status', 'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
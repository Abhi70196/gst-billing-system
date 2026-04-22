<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'proforma_number', 'customer_id', 'date', 'valid_until',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'status', 'converted_invoice_id',
        'terms_conditions', 'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    public function convertedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBill extends Model
{
    protected $fillable = [
        'bill_number', 'vendor_bill_number', 'vendor_id', 'date', 'due_date',
        'subtotal', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'total_amount', 'paid_amount', 'balance_due', 'status', 'notes'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseBillItem::class);
    }
}
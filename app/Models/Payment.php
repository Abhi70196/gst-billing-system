<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'customer_id', 'amount',
        'payment_mode', 'payment_date',
        'reference_no', 'remarks',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
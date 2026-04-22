<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'created_by',
        'invoice_date', 'due_date', 'status',
        'place_of_supply', 'subtotal',
        'cgst_total', 'sgst_total', 'igst_total',
        'cess_total', 'total_amount', 'is_export',
        'is_sez', 'reverse_charge', 'invoice_template',
        'document_hash', 'finalised_at', 'reminder_sent_at', 'notes',
    ];

    protected $casts = [
        'finalised_at'     => 'datetime',
        'reminder_sent_at' => 'datetime',
        'is_export'        => 'boolean',
        'is_sez'           => 'boolean',
        'reverse_charge'   => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
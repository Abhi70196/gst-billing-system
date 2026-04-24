<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfSupply extends Model
{
    protected $table = 'bills_of_supply';

    protected $fillable = [
        'bill_number', 'customer_id', 'date', 'total_amount',
        'status', 'supply_type', 'terms_conditions', 'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(BillOfSupplyItem::class);
    }
}
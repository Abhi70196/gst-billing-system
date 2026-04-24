<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallan extends Model
{
    protected $table = 'delivery_challans';
    protected $fillable = [
        'challan_number', 'customer_id', 'date', 'vehicle_number',
        'driver_name', 'delivery_address', 'delivery_state',
        'delivery_state_code', 'purpose', 'status',
        'dispatched_at', 'delivered_at', 'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryChallanItem::class);
    }
}
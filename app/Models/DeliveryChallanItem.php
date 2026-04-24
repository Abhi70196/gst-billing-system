<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallanItem extends Model
{
    protected $table = 'delivery_challan_items';
    protected $fillable = [
        'delivery_challan_id', 'product_name', 'hsn_sac',
        'description', 'quantity', 'unit', 'unit_price', 'total_amount'
    ];

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class);
    }
}
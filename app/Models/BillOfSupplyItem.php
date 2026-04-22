<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfSupplyItem extends Model
{
    protected $fillable = [
        'bill_of_supply_id', 'product_name', 'hsn_sac', 'description',
        'quantity', 'unit', 'unit_price', 'discount', 'total_amount'
    ];

    public function billOfSupply()
    {
        return $this->belongsTo(BillOfSupply::class);
    }
}
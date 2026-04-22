<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'hsn_sac_code', 'unit_price',
        'gst_rate', 'cess_rate', 'unit',
        'is_exempt', 'is_service',
    ];
}
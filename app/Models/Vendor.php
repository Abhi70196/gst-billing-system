<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'gstin', 'email', 'phone', 'address',
        'state', 'state_code', 'pan', 'contact_person', 'is_active'
    ];

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class);
    }

    public function purchaseBills()
    {
        return $this->hasMany(PurchaseBill::class);
    }
}
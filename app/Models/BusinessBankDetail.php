<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'account_holder',
        'country',
        'bank_name',
        'branch',
        'account_number',
        'account_type',
        'swift_code',
        'order_index',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
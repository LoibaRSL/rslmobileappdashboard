<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessMobileMoneyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'mobile_money_type',
        'order_index',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
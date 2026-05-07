<?php

// app/Models/MobileMoneyDetail.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileMoneyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_registration_id',
        'mobile_money_type',
        'mobile_money_number',
    ];

    public function registration()
    {
        return $this->belongsTo(TinRegistration::class, 'tin_registration_id');
    }
}
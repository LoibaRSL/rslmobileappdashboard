<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDirectorPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'name',
        'tin',
        'order_index',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessWhtDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_wht',
        'withholding_types',
        'services_description',
        'other_withholding_type',
    ];

    protected $casts = [
        'register_for_wht' => 'boolean',
        'withholding_types' => 'array',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
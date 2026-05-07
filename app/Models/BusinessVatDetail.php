<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessVatDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_vat',
        'vat_effective_date',
        'vat_reasons',
        'business_status',
        'previous_owner_name',
        'previous_owner_address',
        'previous_owner_tin',
    ];

    protected $casts = [
        'register_for_vat' => 'boolean',
        'vat_effective_date' => 'date',
        'vat_reasons' => 'array',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
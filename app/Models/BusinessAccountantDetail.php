<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAccountantDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'name',
        'tin',
        'postal_address',
        'postal_code',
        'physical_address',
        'chief_street_name',
        'village',
        'town',
        'district',
        'office_phone',
        'cell_phone',
        'fax1',
        'fax2',
        'email',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
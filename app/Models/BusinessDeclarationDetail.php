<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDeclarationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'declaration_accepted',
        'declaration_name',
        'declaration_capacity',
        'declaration_signature',
        'declaration_date',
    ];

    protected $casts = [
        'declaration_accepted' => 'boolean',
        'declaration_date' => 'date',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
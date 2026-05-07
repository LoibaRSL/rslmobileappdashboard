<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPlasticLevyDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_plastic_levy',
        'plastic_levy_number',
        'plastic_levy_effective_date',
    ];

    protected $casts = [
        'register_for_plastic_levy' => 'boolean',
        'plastic_levy_effective_date' => 'date',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
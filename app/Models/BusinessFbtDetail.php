<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFbtDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_fbt',
        'fringe_benefit_types',
    ];

    protected $casts = [
        'register_for_fbt' => 'boolean',
        'fringe_benefit_types' => 'array',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
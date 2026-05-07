<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessPayeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_paye',
        'paye_employer_date',
        'current_employees',
        'min_annual_salary',
        'max_annual_salary',
    ];

    protected $casts = [
        'register_for_paye' => 'boolean',
        'paye_employer_date' => 'date',
        'current_employees' => 'integer',
        'min_annual_salary' => 'decimal:2',
        'max_annual_salary' => 'decimal:2',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
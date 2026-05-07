<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAntlDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_registration_id',
        'register_for_antl',
        'antl_effective_date',
    ];

    protected $casts = [
        'register_for_antl' => 'boolean',
        'antl_effective_date' => 'date',
    ];

    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }
}
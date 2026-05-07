<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSoleTraderDetail extends Model
{
    use HasFactory;

    protected $table = 'business_sole_trader_details';

    protected $fillable = [
        'business_registration_id',
        'marital_status',
        'marriage_condition',
        'spouse_tin',
        'spouse_name',
        'spouse_maiden_name',
        'spouse_per_id',
        'employers'
    ];

    protected $casts = [
        'employers' => 'array'
    ];

    /**
     * Get the business registration that owns the sole trader details.
     */
    public function businessRegistration()
    {
        return $this->belongsTo(BusinessRegistration::class);
    }

    /**
     * Check if married.
     */
    public function isMarried(): bool
    {
        return $this->marital_status === 'MARRIED';
    }

    /**
     * Check if has spouse information.
     */
    public function hasSpouse(): bool
    {
        return !empty($this->spouse_name);
    }
}